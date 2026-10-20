<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Terminal;

use Beaver\Foundation\Application;

/**
 * Gera relatórios de testes (JUnit, Coverage, etc.).
 *
 * Estrutura:
 *   storage/frankei-reports/{userId}/
 *     ├── current/                  ← último report (todos os planos)
 *     │   ├── junit.xml
 *     │   ├── clover.xml
 *     │   └── coverage/index.html
 *     └── 20261009_183045/          ← histórico (só Pro/Premium)
 *         └── ...
 */
class ReportRunner
{
    private const ALLOWED_TYPES = [
        'junit',     // --log-junit
        'coverage',  // --coverage-html
        'clover',    // --coverage-clover
        'cobertura', // --coverage-cobertura
        'text',      // --coverage-text
        'json',      // --log-junit? (Pest nativo)
    ];

    private string $root;
    private string $reportsDir;

    public function __construct()
    {
        $this->root       = Application::getInstance()->basePath ?? getcwd();
        $this->reportsDir = $this->root . '/storage/frankei-reports';
    }

    /**
     * Gera reports para o utilizador.
     *
     * @param int      $userId   ID do user
     * @param string   $plan     'free' | 'pro' | 'premium' | ...
     * @param string[] $types    ['junit', 'coverage', ...]
     * @param string   $engine   'pest' | 'phpunit' | 'auto'
     * @return array{ok: bool, dir: string, files: array, output: string, error?: string}
     */
    public function generate(int $userId, string $plan, array $types, string $engine = 'auto'): array
    {
        // 1. Filtrar tipos válidos
        $types = array_values(array_intersect($types, self::ALLOWED_TYPES));
        if (empty($types)) {
            return ['ok' => false, 'error' => 'Nenhum tipo válido', 'dir' => '', 'files' => [], 'output' => ''];
        }

        // 1b. Forçar 'clover' sempre que 'coverage' é pedido
        //     (o clover.xml é necessário para a vista bonita do CoverageView)
        if (in_array('coverage', $types, true) && !in_array('clover', $types, true)) {
            $types[] = 'clover';
        }

        // 2. Definir pasta do utilizador
        $userDir = $this->reportsDir . '/' . $userId;
        $isFree  = in_array(strtolower($plan), ['free', ''], true);

        // 3. NÃO apagar o userDir — o cleanDir destruía coverage/clover antes de re-gerar
        $this->ensureDir($userDir);

        // 4. Definir subpasta do report
        $timestamp = date('Ymd_His');
        $subDir    = $isFree ? 'current' : $timestamp;
        $reportDir = $userDir . '/' . $subDir;
        $this->ensureDir($reportDir);

        // 5. Também criar 'current' para pro/premium (aponta para o último)
        if (!$isFree) {
            $currentDir = $userDir . '/current';
            $this->ensureDir($currentDir);
            // Note: não usamos symlink (problemas em PHP+Apache); copiamos no fim
        }

        // FK_REPORT_ALL_TYPES: cache só se TODOS os ficheiros pedidos já existirem
        $cloverPath = $reportDir . '/clover.xml';
        $cloverAge  = is_file($cloverPath) ? (time() - filemtime($cloverPath)) : PHP_INT_MAX;
        $cloverOk   = $cloverAge < 300 && is_file($cloverPath) && filesize($cloverPath) > 1000;

        // Mapear type -> ficheiro esperado
        $esperados = [];
        if (in_array('coverage', $types, true))  $esperados[] = 'coverage/index.html';
        if (in_array('clover', $types, true))    $esperados[] = 'clover.xml';
        if (in_array('cobertura', $types, true)) $esperados[] = 'cobertura.xml';
        if (in_array('text', $types, true))      $esperados[] = 'coverage.txt';
        if (in_array('junit', $types, true))     $esperados[] = 'junit.xml';
        if (in_array('json', $types, true))      $esperados[] = 'junit.json';

        $todosExistem = true;
        foreach ($esperados as $f) {
            if (!is_file($reportDir . '/' . $f)) {
                $todosExistem = false;
                break;
            }
        }

        // Construir argumentos do comando
        $args = $this->buildArgs($types, $reportDir, $engine);

        if ($cloverOk && $todosExistem) {
            // Todos os ficheiros pedidos já existem e o clover é fresco → não re-correr
            $files = $this->listFiles($reportDir);

            return [
                'ok'     => true,
                'dir'    => 'storage/frankei-reports/' . $userId . '/' . $subDir,
                'files'  => $files,
                'output' => "(todos os reports existem e sao frescos — nao re-corri os testes)",
            ];
        }

        // Se faltam ficheiros, apagar o clover para forçar re-execução
        if (!$todosExistem && is_file($cloverPath)) {
            @unlink($cloverPath);
        }

        // 7. Correr 'beaver test' com os args
        $result = (new TerminalRunner($this->root))->run('beaver test ' . $args);

        if (!$result['ok']) {
            return [
                'ok'     => false,
                'dir'    => $reportDir,
                'files'  => [],
                'output' => $result['output'] ?? '',
                'error'  => $result['error'] ?? 'Falha ao correr testes',
            ];
        }

        // 8. Se pro/premium, copiar para 'current'
        if (!$isFree) {
            $this->copyDir($reportDir, $userDir . '/current');
        }

        // 9. Listar ficheiros gerados
        $files = $this->listFiles($reportDir);

        return [
            'ok'     => true,
            'dir'    => 'storage/frankei-reports/' . $userId . '/' . $subDir,
            'files'  => $files,
            'output' => $result['output'],
        ];
    }

    /**
     * Devolve o caminho absoluto de um ficheiro de report.
     * Null se não existir ou não pertencer ao user.
     */
    public function resolveFile(int $userId, string $relative): ?string
    {
        // Segurança: bloquear traversal
        $relative = str_replace(['..', "\0"], '', $relative);
        $relative = ltrim($relative, '/');

        // FK_RESOLVE_FALLBACK: tenta o workspace do user, depois fallback para 0
        $candidatos = [$userId];
        if ($userId !== 0) {
            $candidatos[] = 0; // workspace anónimo / legacy
        }

        foreach ($candidatos as $uid) {
            $base = $this->reportsDir . '/' . $uid;
            $path = realpath($base . '/' . $relative);

            if ($path === false) continue;

            // Confirmar que está dentro do user
            $baseReal = realpath($base);
            if ($baseReal === false || !str_starts_with($path, $baseReal . DIRECTORY_SEPARATOR)) {
                continue;
            }

            if (is_file($path)) return $path;
        }

        return null;
    }

    /** Lista recursiva de ficheiros numa pasta */
    private function listFiles(string $dir): array
    {
        if (!is_dir($dir)) return [];

        $out  = [];
        $base = strlen($dir) + 1;
        $it   = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($it as $f) {
            if (!$f->isFile()) continue;
            $out[] = [
                'path' => ltrim(substr($f->getPathname(), $base), '/'),
                'size' => $f->getSize(),
                'mime' => $this->mimeOf($f->getExtension()),
            ];
        }

        usort($out, fn($a, $b) => strcmp($a['path'], $b['path']));
        return $out;
    }

    /** Constrói os argumentos extra do 'beaver test' conforme os tipos */
    private function buildArgs(array $types, string $reportDir, string $engine): string
    {
        // FK_REPORT_ALL_TYPES: gerar TODOS os formatos pedidos em $types
        $args = [];

        // Motor
        if ($engine === 'pest' || $engine === 'phpunit') {
            $args[] = '--engine=' . $engine;
        }

        // Testsuite default
        $args[] = '--testsuite=Unit';

        // Coverage base (mantém compatibilidade com TestCommand original)
        $args[] = '--coverage';

        // HTML coverage explicito
        if (in_array('coverage', $types, true)) {
            $args[] = '--coverage-html=' . escapeshellarg($reportDir . '/coverage');
        }

        // Clover XML
        if (in_array('clover', $types, true)) {
            $args[] = '--coverage-clover=' . escapeshellarg($reportDir . '/clover.xml');
        }

        // Cobertura XML
        if (in_array('cobertura', $types, true)) {
            $args[] = '--coverage-cobertura=' . escapeshellarg($reportDir . '/cobertura.xml');
        }

        // Coverage Text
        if (in_array('text', $types, true)) {
            $args[] = '--coverage-text=' . escapeshellarg($reportDir . '/coverage.txt');
        }

        // JUnit XML
        if (in_array('junit', $types, true)) {
            $args[] = '--log-junit=' . escapeshellarg($reportDir . '/junit.xml');
        }

        // Json (Pest nativo)
        if (in_array('json', $types, true)) {
            $args[] = '--log-junit=' . escapeshellarg($reportDir . '/junit.json');
        }

        return implode(' ', $args);
    }

    /** Garante que a pasta existe */
    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    /** Apaga recursivamente uma pasta */
    private function cleanDir(string $dir): void
    {
        if (!is_dir($dir)) return;

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($it as $f) {
            if ($f->isDir()) {
                @rmdir($f->getPathname());
            } else {
                @unlink($f->getPathname());
            }
        }
    }

    /** Copia recursivamente uma pasta */
    private function copyDir(string $src, string $dst): void
    {
        $this->cleanDir($dst);
        $this->ensureDir($dst);

        if (!is_dir($src)) return;

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($it as $f) {
            $rel = substr($f->getPathname(), strlen($src) + 1);
            $target = $dst . '/' . $rel;

            if ($f->isDir()) {
                @mkdir($target, 0755, true);
            } else {
                @copy($f->getPathname(), $target);
            }
        }
    }

    /** MIME por extensão */
    private function mimeOf(string $ext): string
    {
        return match (strtolower($ext)) {
            'html', 'htm' => 'text/html',
            'xml'         => 'application/xml',
            'json'        => 'application/json',
            'txt'         => 'text/plain',
            'css'         => 'text/css',
            'js'          => 'application/javascript',
            'svg'         => 'image/svg+xml',
            default       => 'application/octet-stream',
        };
    }
}
