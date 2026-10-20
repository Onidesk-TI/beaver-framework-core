<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Scanners\Passwords;

use Beaver\Plugins\Frankei\Frankei\Code\CodeScannerInterface;
use Beaver\Plugins\Frankei\Frankei\Code\CodeScanContext;
use Beaver\Plugins\Frankei\Frankei\Finding;

class WeakPasswordHashScanner implements CodeScannerInterface
{
    public function id(): string              { return 'weak-password-hash'; }
    public function name(): string            { return 'Hashes de password fracos'; }
    public function category(): string        { return 'password'; }
    public function defaultSeverity(): string { return 'high'; }

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
        $lower = strtolower($line);

        // md5 / sha1 com "pass", "senha", "password", "pwd"
        if (preg_match('/\b(md5|sha1|crypt)\s*\(/i', $line, $m)) {
            if ($this->looksLikePassword($line)) {
                return $this->finding($file, $n, $line, strtolower($m[1]),
                    'Hash de password com ' . strtolower($m[1]) . '() é inseguro.');
            }
        }

        // password_hash com MD5/SHA1 (constante)
        if (preg_match('/password_hash\s*\([^,]+,\s*(MD5|SHA1)\b/i', $line, $m)) {
            return $this->finding($file, $n, $line, strtolower($m[1]),
                'password_hash() com algoritmo ' . strtoupper($m[1]) . ' está descontinuado.');
        }

        // Comparação de passwords com == (timing attack)
        if (preg_match('/\$(pass|senha|password|pwd)\w*\s*==\s*\$/', $line)) {
            return new Finding(
                scanner:     $this->id(),
                category:    $this->category(),
                severity:    'high',
                title:       'Comparação de password com == em ' . $file . ':' . $n,
                description: 'Comparação direta de passwords é vulnerável a timing attacks.',
                suggestion:  "Usa password_verify():\n\nif (password_verify(\$input, \$hash)) {\n    // ok\n}",
                impact:      'Um atacante pode descobrir a password medindo tempos de resposta.',
                reference:   'https://www.php.net/manual/en/function.password-verify.php',
                data:        ['file' => $file, 'line' => $n, 'code' => trim($line)],
            );
        }

        return null;
    }

    private function looksLikePassword(string $line): bool
    {
        return (bool) preg_match('/pass|senha|pwd|password|hash/i', $line);
    }

    private function finding(string $file, int $n, string $line, string $algo, string $desc): Finding
    {
        return new Finding(
            scanner:     $this->id(),
            category:    $this->category(),
            severity:    $algo === 'md5' || $algo === 'sha1' ? 'high' : 'medium',
            title:       'Hash de password fraco (' . strtoupper($algo) . ') em ' . $file . ':' . $n,
            description: $desc,
            suggestion:  "Usa password_hash() com Argon2id:\n\n\$hash = password_hash(\$password, PASSWORD_ARGON2ID);\n\n// Verificar:\nif (password_verify(\$input, \$hash)) { /* ok */ }",
            impact:      'Hashes MD5/SHA1 são extremamente rápidos de quebrar por força bruta ou rainbow tables.',
            reference:   'https://www.php.net/manual/en/function.password-hash.php',
            data:        ['file' => $file, 'line' => $n, 'code' => trim($line), 'algorithm' => $algo],
        );
    }
}
