<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Scanners\Security;

use Beaver\Plugins\Frankei\Frankei\Code\CodeScannerInterface;
use Beaver\Plugins\Frankei\Frankei\Code\CodeScanContext;
use Beaver\Plugins\Frankei\Frankei\Finding;

class PhpCommandInjectionScanner implements CodeScannerInterface
{
    public function id(): string              { return 'php-cmdi'; }
    public function name(): string            { return 'Command Injection'; }
    public function category(): string        { return 'security'; }
    public function defaultSeverity(): string { return 'critical'; }

    private array $fns = [
        'exec', 'shell_exec', 'system', 'passthru', 'popen', 'proc_open',
        'pcntl_exec', 'pfsockopen',
    ];

    public function scan(array $files, string $root, array $options = []): array
    {
        $ctx = new CodeScanContext($root, 1048576, $options);
        $findings = [];

        foreach ($files as $file) {
            $lines = $ctx->readLines($file);
            $rel   = $ctx->relPath($file);

            foreach ($lines as $n => $line) {
                if ($ctx->isComment($line)) continue;
                $f = $this->checkLine($line, $rel, $n);
                if ($f !== null) $findings[] = $f;
            }
        }

        return $findings;
    }

        private function checkLine(string $line, string $file, int $n): ?Finding
    {
        // Ignorar strings / SQL
        if (preg_match('/^\s*[\'"]/', $line)) return null;
        if (preg_match('/\$(sql|ddl|sets|query|q|stmt)\s*=/i', $line)) return null;

        // Ignorar métodos estáticos (SiteDB::exec, UserService::exec, etc.)
        if (preg_match('/\b[A-Z][A-Za-z0-9_]*::(exec|system|shell_exec|query)\s*\(/i', $line)) return null;

        // Ignorar ->exec(), ->query(), self::exec, $this->exec
        if (preg_match('/(self|static|\$this)->(exec|system|shell_exec)\s*\(/i', $line)) return null;
        if (preg_match('/(self|static)::(exec|system|shell_exec)\s*\(/i', $line)) return null;
        if (preg_match('/->(exec|query|prepare|execute)\s*\(/i', $line)) return null;

        // Ignorar declarações de funções
        if (preg_match('/\bfunction\s+(exec|system|passthru|shell_exec)\s*\(/i', $line)) return null;

        $fns = implode('|', $this->fns);

        // Só alertar se houver input do utilizador direto na mesma linha
        $hasUserInput = (bool) preg_match('/\$_(GET|POST|REQUEST|COOKIE)/', $line);

        if (!$hasUserInput) return null;

        if (preg_match('/@?\b(' . $fns . ')\s*\(/i', $line, $m)) {
            if ($this->isSafe($line)) return null;

            return $this->finding($file, $n, $line, strtolower($m[1]),
                'Chamada a ' . $m[1] . '() com input do utilizador.'
            );
        }

        // Backticks com input do utilizador
        if (preg_match('/`[^`]*\$_/', $line)) {
            if ($this->isSafe($line)) return null;

            return $this->finding($file, $n, $line, 'backtick',
                'Execucao de comando com input do utilizador.'
            );
        }

        return null;
    }

    private function isSafe(string $line): bool
    {
        // escapeshellarg / escapeshellcmd = seguro
        return str_contains($line, 'escapeshellarg')
            || str_contains($line, 'escapeshellcmd');
    }

    private function finding(string $file, int $n, string $line, string $fn, string $desc): Finding
    {
        return new Finding(
            scanner:     $this->id(),
            category:    $this->category(),
            severity:    'critical',
            title:       'Command Injection em ' . $file . ':' . $n,
            description: $desc,
            suggestion:  "Se o input tem de ser passado ao shell, escapa-o:\n\n\$safe = escapeshellarg(\$input);\nexec('comando ' . \$safe, \$output);\n\nMelhor ainda: evita chamadas ao shell e usa APIs nativas do PHP.",
            impact:      'Um atacante pode executar comandos arbitrários no servidor, ler/escrever ficheiros, instalar backdoors, etc.',
            reference:   'https://owasp.org/www-community/attacks/Command_Injection',
            data:        ['file' => $file, 'line' => $n, 'code' => trim($line), 'function' => $fn],
        );
    }
}
