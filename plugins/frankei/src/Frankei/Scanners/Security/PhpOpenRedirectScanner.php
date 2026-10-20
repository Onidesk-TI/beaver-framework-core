<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Scanners\Security;

use Beaver\Plugins\Frankei\Frankei\Code\CodeScannerInterface;
use Beaver\Plugins\Frankei\Frankei\Code\CodeScanContext;
use Beaver\Plugins\Frankei\Frankei\Finding;

class PhpOpenRedirectScanner implements CodeScannerInterface
{
    public function id(): string              { return 'php-openredirect'; }
    public function name(): string            { return 'Open Redirect'; }
    public function category(): string        { return 'security'; }
    public function defaultSeverity(): string { return 'medium'; }

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
        // Ignorar strings
        if (preg_match('/^\s*[\'"]/', $line)) return null;

        // Só alertar se houver superglobal direta na mesma linha
        if (!preg_match('/\$_(GET|POST|REQUEST|COOKIE)/', $line)) return null;

        // header('Location: ...') com superglobal
        if (preg_match('/\bheader\s*\(\s*[\'"]Location:\s*[\'"][^)]*\$_(GET|POST|REQUEST|COOKIE)/i', $line)) {
            if ($this->isSafe($line)) return null;

            return $this->finding($file, $n, $line,
                'header(Location: ...) com input direto do utilizador.'
            );
        }

        // wp_redirect, redirect() com input
        if (preg_match('/\b(redirect|wp_redirect)\s*\([^)]*\$_(GET|POST|REQUEST|COOKIE)/i', $line, $m)) {
            if ($this->isSafe($line)) return null;

            return $this->finding($file, $n, $line,
                $m[1] . '() com input direto do utilizador.'
            );
        }

        // Header::location / Redirect::to com superglobal
        if (preg_match('/(Header|Redirect|Response)::(location|to|redirect)\s*\([^)]*\$_(GET|POST|REQUEST)/i', $line, $m)) {
            if ($this->isSafe($line)) return null;

            return $this->finding($file, $n, $line,
                $m[1] . '::' . $m[2] . '() com input direto.'
            );
        }

        return null;
    }

    private function isSafe(string $line): bool
    {
        // Se já tem validação na mesma linha
        return str_contains($line, 'filter_var')
            || str_contains($line, 'FILTER_VALIDATE_URL')
            || str_contains($line, 'parse_url')
            || str_contains($line, 'allowlist')
            || str_contains($line, 'in_array');
    }

    private function finding(string $file, int $n, string $line, string $desc): Finding
    {
        return new Finding(
            scanner:     $this->id(),
            category:    $this->category(),
            severity:    'medium',
            title:       'Possivel Open Redirect em ' . $file . ':' . $n,
            description: $desc,
            suggestion:  "Valida o destino contra uma allowlist:\n\n\$allowed = ['/', '/painel', '/loja'];\n\$dest = in_array(\$_GET['next'] ?? '/', \$allowed, true) ? \$_GET['next'] : '/';",
            impact:      'Um atacante pode redirecionar o utilizador para um site malicioso passando um URL controlado.',
            reference:   'https://owasp.org/www-community/attacks/Unvalidated_Redirects_and_Forwards_Cheat_Sheet',
            data:        ['file' => $file, 'line' => $n, 'code' => trim($line)],
        );
    }
}
