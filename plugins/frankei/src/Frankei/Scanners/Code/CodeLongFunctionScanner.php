<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Scanners\Code;

use Beaver\Plugins\Frankei\Frankei\Code\CodeScannerInterface;
use Beaver\Plugins\Frankei\Frankei\Code\CodeScanContext;
use Beaver\Plugins\Frankei\Frankei\Finding;

class CodeLongFunctionScanner implements CodeScannerInterface
{
    public function id(): string              { return 'code-long-function'; }
    public function name(): string            { return 'Funcoes longas'; }
    public function category(): string        { return 'complexity'; }
    public function defaultSeverity(): string { return 'info'; }

    public function scan(array $files, string $root, array $options = []): array
    {
        $ctx = new CodeScanContext($root, 1048576, $options);
        $findings = [];
        $maxLines = max(10, min(500, (int) $ctx->opt('maxFunctionLines', 50)));

        foreach ($files as $file) {
            $lines = $ctx->readLines($file);
            $rel   = $ctx->relPath($file);

            $inFunc   = false;
            $start    = 0;
            $depth    = 0;
            $startLine = 0;

            foreach ($lines as $n => $line) {
                if (!$inFunc) {
                    // Detetar inicio de funcao
                    if (preg_match('/\bfunction\s+[a-zA-Z_]\w*\s*\(/', $line)) {
                        $inFunc   = true;
                        $start    = $n;
                        $startLine = $n;
                        $depth    = 0;
                    }
                }

                if ($inFunc) {
                    $depth += substr_count($line, '{');
                    $depth -= substr_count($line, '}');

                    if ($depth <= 0 && $n > $start) {
                        $length = $n - $start + 1;
                        if ($length > $maxLines) {
                            $findings[] = $this->finding($rel, $start, $length, $maxLines, $lines[$start]);
                        }
                        $inFunc = false;
                    }
                }
            }
        }

        return $findings;
    }

    private function finding(string $file, int $startLine, int $length, int $max, string $firstLine): Finding
    {
        return new Finding(
            scanner:     $this->id(),
            category:    $this->category(),
            severity:    'info',
            title:       'Funcao com ' . $length . ' linhas em ' . $file . ':' . $startLine,
            description: 'Funcao excede ' . $max . ' linhas (tem ' . $length . ').',
            suggestion:  "Divide em funcoes menores com responsabilidade unica.",
            impact:      'Funcoes longas sao dificeis de testar, entender e manter.',
            reference:   'https://refactoring.guru/smells/long-function',
            data:        ['file' => $file, 'line' => $startLine, 'code' => trim($firstLine), 'length' => $length],
        );
    }
}
