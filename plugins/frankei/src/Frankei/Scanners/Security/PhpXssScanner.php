<?php

declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Scanners\Security;

use Beaver\Plugins\Frankei\Frankei\Code\CodeScannerInterface;
use Beaver\Plugins\Frankei\Frankei\Code\CodeScanContext;
use Beaver\Plugins\Frankei\Frankei\Finding;

class PhpXssScanner implements CodeScannerInterface
{
    public function id(): string
    {
        return 'php-xss';
    }
    public function name(): string
    {
        return 'XSS em codigo PHP';
    }
    public function category(): string
    {
        return 'security';
    }
    public function defaultSeverity(): string
    {
        return 'high';
    }

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
        // Ignorar ficheiros CLI (não é HTML)
        if (str_contains($line, 'PHP_EOL')) return null;

        // Só alertar se houver variável
        if (!preg_match('/\$[A-Za-z_]/', $line)) return null;

        $risky = false;
        $pattern = '';


        if (preg_match('/<\?=\s*\$[A-Za-z_][A-Za-z0-9_\[\]\'\"\$>-]*\s*\?>/', $line, $m)) { /*     <?= $var ?> */
            $pattern = $m[0];
        }
        // echo $var
        elseif (preg_match('/\becho\s+\$[A-Za-z_][A-Za-z0-9_\[\]\'\"\$>-]*/', $line, $m)) {
            $risky = true;
            $pattern = $m[0];
        }
        // print $var
        elseif (preg_match('/\bprint\s+\$[A-Za-z_][A-Za-z0-9_\[\]\'\"\$>-]*/', $line, $m)) {
            $risky = true;
            $pattern = $m[0];
        }
        // Superglobals em output
        elseif (preg_match('/(echo|print|<\?=)\s*[^;]*\$_(GET|POST|REQUEST|COOKIE|SERVER)\b/', $line, $m)) {
            $risky = true;
            $pattern = trim($m[0]);
        }

        if (!$risky) return null;
        if ($this->hasEscape($line)) return null;

        // Ignorar output interno ($this->content, $this->get)
        if (preg_match('/\$this->(content|body|html|output)\b/i', $line)) return null;
        if (preg_match('/\$this->(get|render|make|view)\s*\(/i', $line)) return null;

        // Ignorar ficheiros de framework
        if (preg_match('#/(Response|Translator|View|Kernel|Router)\.php$#i', $file)) return null;

        // Ignorar variáveis claramente numéricas/contagens
        if (preg_match('/\$(total|count|num|id|idx|i|n|length|size|qty)\w*\b/i', $pattern)) return null;

        return new Finding(
            scanner: $this->id(),
            category: $this->category(),
            severity: 'high',
            title: 'Possivel XSS em ' . $file . ':' . $n,
            description: 'Saida de variavel sem escape: ' . trim($pattern),
            suggestion: $this->suggest($line),
            impact: 'Um atacante pode injetar JavaScript que corre no browser das vitimas.',
            reference: 'https://owasp.org/www-community/attacks/xss/',
            data: ['file' => $file, 'line' => $n, 'code' => trim($line)],
        );
    }

    private function hasEscape(string $line): bool
    {
        foreach (['htmlspecialchars', 'htmlentities', 'strip_tags', 'e(', 'esc('] as $fn) {
            if (str_contains($line, $fn)) return true;
        }
        return false;
    }

    private function suggest(string $line): string
    {
        $lt  = chr(60);   // <
        $gt  = chr(62);   // >
        $php = chr(63);   // ?


        $pattern1 = '/' . $lt . '\\' . $php . '=\\s*(.+?)\\s*\\' . $php . $gt . '/';
        if (preg_match($pattern1, $line, $m)) {
            $expr = trim($m[1]);
            $open  = $lt . $php . '=';
            $close = ' ' . $php . $gt;
            return $open . ' htmlspecialchars(' . $expr . ", ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')" . $close;
        }

        // Caso 2: echo/print
        if (preg_match('/\b(echo|print)\s+(.+?)\s*;?\s*$/', $line, $m)) {
            $stmt = $m[2];
            $novoStmt = preg_replace(
                '/(\$[A-Za-z_][A-Za-z0-9_\[\]\'"\$>-]*(?:\s*\?\?\s*[^,)]+)?)/',
                "htmlspecialchars(\$1, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')",
                $stmt
            );
            if ($novoStmt !== $stmt) {
                return str_replace($stmt, $novoStmt, $line);
            }
        }

        /* Fallback */
        return $line;
    }
}
