<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Scanners\Security;

use Beaver\Plugins\Frankei\Frankei\Code\CodeScannerInterface;
use Beaver\Plugins\Frankei\Frankei\Code\CodeScanContext;
use Beaver\Plugins\Frankei\Frankei\Finding;

class PhpSqlInjectionScanner implements CodeScannerInterface
{
    public function id(): string              { return 'php-sqli'; }
    public function name(): string            { return 'SQL Injection em código PHP'; }
    public function category(): string        { return 'security'; }
    public function defaultSeverity(): string { return 'critical'; }

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
        // Ignorar linhas que são strings (código de exemplo)
        if (preg_match('/^\s*[\'"]/', $line)) return null;

        // Ignorar PRAGMA (operações internas)
        if (stripos($line, 'PRAGMA') !== false) return null;

        // Ignorar ->prepare() (é a solução, não o problema)
        if (preg_match('/->prepare\s*\(/', $line)) return null;

        // Só alertar se houver input do utilizador direto
        $hasUserInput = (bool) preg_match('/\$_(GET|POST|REQUEST|COOKIE|FILES)/', $line);

        if (!$hasUserInput) return null;

        // 1. Query/exec direto com input
        if (preg_match('/(->(query|exec)\s*\(|mysqli_query\s*\()/i', $line)) {
            return $this->finding($file, $n, $line,
                'Query SQL com input direto do utilizador.'
            );
        }

        // 2. Concatenação com input em query
        if (preg_match('/(SELECT|INSERT|UPDATE|DELETE|WHERE)[^;]*"\s*\.\s*\$|\$\s*\.\s*"(SELECT|INSERT|UPDATE|DELETE|WHERE)/i', $line)) {
            return $this->finding($file, $n, $line,
                'Query SQL a concatenar strings com input.'
            );
        }

        return null;
    }

    private function hasPlaceholder(string $line): bool
    {
        return str_contains($line, '?')
            || stripos($line, 'bindParam') !== false
            || stripos($line, 'bindValue') !== false
            || preg_match('/:\w+/', $line) === 1;
    }

    private function finding(string $file, int $n, string $line, string $desc): Finding
    {
        return new Finding(
            scanner:     $this->id(),
            category:    $this->category(),
            severity:    'critical',
            title:       'Possível SQL Injection em ' . $file . ':' . $n,
            description: $desc,
            suggestion:  "Usa prepared statements:\n\n\$stmt = \$pdo->prepare('SELECT * FROM users WHERE id = ?');\n\$stmt->execute([\$id]);\n\$row = \$stmt->fetch();",
            impact:      'Um atacante pode ler, alterar ou apagar dados, contornar autenticação ou executar comandos na base de dados.',
            reference:   'https://owasp.org/www-community/attacks/SQL_Injection',
            data:        ['file' => $file, 'line' => $n, 'code' => trim($line)],
        );
    }
}
