<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Scanners\Code;

use Beaver\Plugins\Frankei\Frankei\Code\CodeScannerInterface;
use Beaver\Plugins\Frankei\Frankei\Code\CodeScanContext;
use Beaver\Plugins\Frankei\Frankei\Finding;

class CodeErrorSuppressionScanner implements CodeScannerInterface
{
    public function id(): string              { return 'code-error-suppression'; }
    public function name(): string            { return 'Supressao de erros (@)'; }
    public function category(): string        { return 'style'; }
    public function defaultSeverity(): string { return 'info'; }

    public function scan(array $files, string $root, array $options = []): array
    {
        $ctx = new CodeScanContext($root, 1048576, $options);
        $findings = [];

        foreach ($files as $file) {
            $lines = $ctx->readLines($file);
            $rel   = $ctx->relPath($file);

            foreach ($lines as $n => $line) {
                if ($ctx->isComment($line)) continue;

                // Ignorar linhas que sao strings
                if (preg_match('/^\s*[\'"]/', $line)) continue;

                // Ignorar "@" em anotacoes (docblock, atributos PHP 8)
                if (preg_match('/^\s*\*|^\s*#\[/', $line)) continue;

                // Detetar @ antes de chamada de funcao
                if (preg_match('/@\s*[a-zA-Z_]/', $line)) {
                    // Ignorar emails, arrays, etc.
                    if (preg_match('/[a-zA-Z0-9]@[a-zA-Z0-9]/', $line)) continue;

                    $findings[] = $this->finding($rel, $n, $line);
                }
            }
        }

        return $findings;
    }

    private function finding(string $file, int $n, string $line): Finding
    {
        return new Finding(
            scanner:     $this->id(),
            category:    $this->category(),
            severity:    'info',
            title:       'Supressao @ em ' . $file . ':' . $n,
            description: 'Operador @ suprime warnings/erros.',
            suggestion:  "Trata o erro explicitamente em vez de o esconder:\n\ntry {\n    \$result = funcao();\n} catch (\\Throwable \$e) {\n    // tratar\n}",
            impact:      'Erros silenciosos dificultam debugging e podem esconder bugs.',
            reference:   'https://www.php.net/manual/en/language.operators.errorcontrol.php',
            data:        ['file' => $file, 'line' => $n, 'code' => trim($line)],
        );
    }
}
