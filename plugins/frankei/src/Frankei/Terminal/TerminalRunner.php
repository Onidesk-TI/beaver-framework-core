<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Terminal;

/**
 * Executa comandos `beaver ...` de forma controlada.
 *
 * Segurança:
 *  - whitelist de subcomandos
 *  - sem shell (proc_open com array de args)
 *  - timeout
 *  - cwd restrito ao projeto
 */
class TerminalRunner
{
    /**
     * Subcomandos permitidos.
     *
     * Critério:
     *  - Read-only (list, status, info, show, help, about, test)
     *  - Dev-friendly (make:*, theme:*, cache:clear)
     *  - NÃO perigoso (não apaga BD, não instala, não bloqueia)
     */
    private const ALLOWED_COMMANDS = [
        // Info / Ajuda
        'help', 'about',

        // Testes
        'test',

        // Plugin
        'plugin:list', 'plugin:info', 'plugin:audit', 'plugin:validate',

        // Migrations / BD (apenas status)
        'migrate:status', 'db:status',

        // Queue / Jobs (apenas leitura)
        'queue:list',
        'job:list',

        // Drafts
        'draft:list',

        // Cache
        'cache:clear',

        // Mobile (read-only)
        'mobile:list', 'mobile:info', 'mobile:screens',

        // Themes
        'theme:list',

        // Cron (sync é seguro)
        'cron:sync',

        // Make: (geram ficheiros, sem apagar nada)
        'make:controller', 'make:model', 'make:migration',
        'make:middleware', 'make:request', 'make:seeder',
        'make:factory', 'make:plugin',
    ];

    private const DEFAULT_TIMEOUT = 60;
    private const MAX_OUTPUT_BYTES = 1024 * 1024; // 1MB

    public function __construct(
        private string $projectRoot,
        private string $beaverBin = 'beaver',
    ) {}

    /**
     * @return array{ok: bool, output: string, exit_code: int, elapsed_ms: int, error?: string}
     */
    public function run(string $commandLine, ?int $timeout = null): array
    {
        $timeout = $timeout ?? self::DEFAULT_TIMEOUT;

        // 1. Normalizar: aceita "beaver test", "test", "beaver test --filter=x"
        $commandLine = trim($commandLine);
        if ($commandLine === '') {
            return $this->fail('Comando vazio');
        }

        // 2. Remover prefixo "beaver" se existir
        if (str_starts_with($commandLine, 'beaver ')) {
            $commandLine = substr($commandLine, 7);
        } elseif ($commandLine === 'beaver') {
            $commandLine = 'help';
        }

        // 3. Parse de args (respeitar aspas simples/duplas)
        $args = $this->parseArgs($commandLine);
        if ($args === []) {
            return $this->fail('Comando vazio');
        }

        // 4. Validar contra whitelist
        $subcommand = $args[0];
        if (!in_array($subcommand, self::ALLOWED_COMMANDS, true)) {
            return $this->fail("Comando não permitido: {$subcommand}");
        }

        // 5. Verificar binário
        $binary = $this->projectRoot . '/' . $this->beaverBin;
        if (!is_file($binary)) {
            return $this->fail("Binário não encontrado: {$binary}");
        }
        if (!is_executable($binary)) {
            return $this->fail("Binário não executável: {$binary}");
        }

        // 6. Executar via proc_open (sem shell!)
        $cmd = array_merge(['php', $binary], $args);

        return $this->execute($cmd, $timeout);
    }

    /**
     * @param string[] $cmd
     * @return array{ok: bool, output: string, exit_code: int, elapsed_ms: int, error?: string}
     */
    private function execute(array $cmd, int $timeout): array
    {
        $t0 = microtime(true);

        $descriptors = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w'],  // stderr
        ];

        error_log('[TerminalRunner] cmd=' . json_encode($cmd));
        error_log('[TerminalRunner] cwd=' . $this->projectRoot);
        $process = proc_open($cmd, $descriptors, $pipes, $this->projectRoot, $this->env());
        if (!is_resource($process)) {
            return $this->fail('Falha ao abrir processo');
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $output   = '';
        $start    = time();
        $timedOut = false;

        while (true) {
            $status = proc_get_status($process);

            // Ler buffers
            $out = stream_get_contents($pipes[1]);
            $err = stream_get_contents($pipes[2]);
            if ($out !== false) $output .= $out;
            if ($err !== false) $output .= $err;

            // Limite de output
            if (strlen($output) > self::MAX_OUTPUT_BYTES) {
                $output .= "\n[... output truncado ...]";
                proc_terminate($process, 9);
                break;
            }

            if (!$status['running']) {
                break;
            }

            if (time() - $start > $timeout) {
                proc_terminate($process, 9);
                $timedOut = true;
                break;
            }

            usleep(50000); // 50ms
        }

        // Última leitura
        $output .= stream_get_contents($pipes[1]) ?: '';
        $output .= stream_get_contents($pipes[2]) ?: '';

        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        $elapsed = (int) round((microtime(true) - $t0) * 1000);

        if ($timedOut) {
            return [
                'ok'         => false,
                'output'     => $output,
                'exit_code'  => -1,
                'elapsed_ms' => $elapsed,
                'error'      => "Timeout após {$timeout}s",
            ];
        }

        // Limpar códigos ANSI (cores do terminal)
        $output = preg_replace('/\x1b\[[0-9;]*[a-zA-Z]/', '', $output) ?? $output;
        // Normalizar line endings (Windows → Unix)
        $output = str_replace("\r\n", "\n", $output);
        $output = str_replace("\r", "\n", $output);

        return [
            'ok'         => $exitCode === 0,
            'output'     => $output,
            'exit_code'  => $exitCode,
            'elapsed_ms' => $elapsed,
        ];
    }

    /**
     * Parse de argumentos respeitando aspas.
     * Ex: "test --filter='foo bar'" → ['test', '--filter=foo bar']
     *
     * @return string[]
     */
    private function parseArgs(string $line): array
    {
        $args = [];
        $buf  = '';
        $inQuote = null; // "'" ou '"' ou null

        $len = strlen($line);
        for ($i = 0; $i < $len; $i++) {
            $ch = $line[$i];

            if ($inQuote !== null) {
                if ($ch === $inQuote) {
                    $inQuote = null;
                } else {
                    $buf .= $ch;
                }
            } elseif ($ch === "'" || $ch === '"') {
                $inQuote = $ch;
            } elseif ($ch === ' ' || $ch === "\t") {
                if ($buf !== '') {
                    $args[] = $buf;
                    $buf = '';
                }
            } else {
                $buf .= $ch;
            }
        }

        if ($buf !== '') {
            $args[] = $buf;
        }

        return $args;
    }

    /** @return array<string, string> */
    private function env(): array
    {
        // Resolver user da sessão HTTP (0 em CLI puro)
        $userId = 0;
        try {
            if (class_exists('\\Beaver\\Auth\\Auth')) {
                $userId = (int) (\Beaver\Auth\Auth::id() ?? 0);
            }
        } catch (\Throwable $e) {}

        return [
            'FRANKEI_USER_ID' => (string) $userId,
            'PATH'        => getenv('PATH') ?: '/usr/bin:/bin',
            'HOME'        => getenv('HOME') ?: '/tmp',
            'LANG'        => 'C.UTF-8',
            'APP_ENV'     => 'testing',
            'COLUMNS'     => '120',
            // Forçar cores ANSI mesmo sem TTY
            'TERM'        => 'xterm-256color',
            'FORCE_COLOR' => '1',
            // Xdebug: ativar modo coverage (necessário para coverage reports)
            'XDEBUG_MODE' => 'coverage',
        ];
    }

    /** @return array{ok: bool, output: string, exit_code: int, elapsed_ms: int, error: string} */
    private function fail(string $error): array
    {
        return [
            'ok'         => false,
            'output'     => '',
            'exit_code'  => -1,
            'elapsed_ms' => 0,
            'error'      => $error,
        ];
    }

    /** Metadados para o frontend (autocomplete) */
    public static function metadata(): array
    {
        return [
            'whitelist' => self::ALLOWED_COMMANDS,
            'timeout'   => self::DEFAULT_TIMEOUT,
        ];
    }
}
