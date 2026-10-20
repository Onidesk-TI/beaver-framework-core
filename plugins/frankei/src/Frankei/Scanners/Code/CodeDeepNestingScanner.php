<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Scanners\Code;

use Beaver\Plugins\Frankei\Frankei\Code\CodeScannerInterface;
use Beaver\Plugins\Frankei\Frankei\Code\CodeScanContext;
use Beaver\Plugins\Frankei\Frankei\Finding;

class CodeDeepNestingScanner implements CodeScannerInterface
{
    public function id(): string              { return 'code-deep-nesting'; }
    public function name(): string            { return 'Aninhamento profundo'; }
    public function category(): string        { return 'complexity'; }
    public function defaultSeverity(): string { return 'info'; }

    public function scan(array $files, string $root, array $options = []): array
    {
        $ctx = new CodeScanContext($root, 1048576, $options);
        $findings = [];
        $maxDepth = max(2, min(10, (int) $ctx->opt('maxNestingDepth', 4)));

        foreach ($files as $file) {
            $lines = $ctx->readLines($file);
            $rel   = $ctx->relPath($file);

            $depth    = 0;
            $maxSeen  = 0;
            $jaAvisou = []; // depth => true (para nao repetir)

            foreach ($lines as $n => $line) {
                if ($ctx->isComment($line)) continue;

                $opens  = substr_count($line, '{');
                $closes = substr_count($line, '}');
                $depth += $opens - $closes;

                if ($depth > $maxSeen) {
                    $maxSeen = $depth;
                }

                // Reportar apenas quando ENTRA num novo nivel acima do limite
                if ($depth > $maxDepth && !isset($jaAvisou[$depth])) {
                    $findings[] = $this->finding($rel, $n, $line, $depth, $maxDepth);
                    $jaAvisou[$depth] = true;
                }

                // Se desceu ao limite ou abaixo, reset (permite reportar de novo)
                if ($depth <= $maxDepth) {
                    $jaAvisou = [];
                }

                if ($depth < 0) $depth = 0;
            }
        }

        return $findings;
    }

    private function finding(string $file, int $n, string $line, int $depth, int $max): Finding
    {
        return new Finding(
            scanner:     $this->id(),
            category:    $this->category(),
            severity:    'info',
            title:       'Aninhamento ' . $depth . ' niveis em ' . $file . ':' . $n,
            description: 'Bloco com ' . $depth . ' niveis (max recomendado: ' . $max . ').',
            suggestion:  "Extrai blocos internos para funcoes ou usa early returns.",
            impact:      'Aninhamento profundo reduz legibilidade e aumenta complexidade ciclomatica.',
            reference:   'https://refactoring.guru/smells/long-function',
            data:        ['file' => $file, 'line' => $n, 'code' => trim($line), 'depth' => $depth],
        );
    }
}
