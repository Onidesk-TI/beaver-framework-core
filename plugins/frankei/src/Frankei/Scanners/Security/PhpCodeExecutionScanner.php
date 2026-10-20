<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Scanners\Security;

use Beaver\Plugins\Frankei\Frankei\Code\CodeScannerInterface;
use Beaver\Plugins\Frankei\Frankei\Code\CodeScanContext;
use Beaver\Plugins\Frankei\Frankei\Finding;

class PhpCodeExecutionScanner implements CodeScannerInterface
{
    public function id(): string              { return 'php-code-exec'; }
    public function name(): string            { return 'Execução de código dinâmico'; }
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
        // eval(...) com qualquer coisa
        if (preg_match('/\beval\s*\(/i', $line, $m)) {
            return $this->finding($file, $n, $line, 'eval',
                'Uso de eval() — executa código PHP arbitrário.'
            );
        }

        // assert() com string (executável até PHP 7)
        if (preg_match('/\bassert\s*\(\s*[\'"]/i', $line, $m)) {
            return $this->finding($file, $n, $line, 'assert',
                'assert() com string pode executar código.'
            );
        }

        // create_function (deprecated e inseguro)
        if (preg_match('/\bcreate_function\s*\(/i', $line, $m)) {
            return $this->finding($file, $n, $line, 'create_function',
                'create_function() está descontinuado e é inseguro.'
            );
        }

        // preg_replace com /e modifier (executável até PHP 7)
        if (preg_match('/preg_replace\s*\([^,]+[\'"]\/[^\'"]*e[\'"]/i', $line, $m)) {
            return $this->finding($file, $n, $line, 'preg_replace/e',
                'preg_replace() com modificador /e executa código.'
            );
        }

        // call_user_func / call_user_func_array com variável
        if (preg_match('/\bcall_user_func(_array)?\s*\(\s*\$[A-Za-z_]/i', $line, $m)) {
            return $this->finding($file, $n, $line, 'call_user_func',
                'call_user_func() com função controlável pelo utilizador.'
            );
        }

        return null;
    }

    private function finding(string $file, int $n, string $line, string $fn, string $desc): Finding
    {
        return new Finding(
            scanner:     $this->id(),
            category:    $this->category(),
            severity:    'critical',
            title:       'Execução de código em ' . $file . ':' . $n,
            description: $desc . ' (' . $fn . ')',
            suggestion:  "Evita " . $fn . "(). Se precisas de lógica dinâmica, usa um match()/switch com allowlist de operações permitidas.",
            impact:      'Um atacante pode executar código PHP arbitrário no servidor — comprometer a app, ler dados sensíveis, escalar privilégios.',
            reference:   'https://owasp.org/www-community/vulnerabilities/Code_Injection',
            data:        ['file' => $file, 'line' => $n, 'code' => trim($line), 'function' => $fn],
        );
    }
}
