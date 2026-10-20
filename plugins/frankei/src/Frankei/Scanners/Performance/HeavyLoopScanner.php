<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Scanners\Performance;

use Beaver\Plugins\Frankei\Frankei\Code\CodeScannerInterface;
use Beaver\Plugins\Frankei\Frankei\Code\CodeScanContext;
use Beaver\Plugins\Frankei\Frankei\Finding;

class HeavyLoopScanner implements CodeScannerInterface
{
    public function id(): string              { return 'perf-heavy-loop'; }
    public function name(): string            { return 'Loops aninhados com queries'; }
    public function category(): string        { return 'performance'; }
    public function defaultSeverity(): string { return 'medium'; }

    private array $dbPatterns = [
        '->query\s*\(',
        '->exec\s*\(',
        '->prepare\s*\(',
        '->find\s*\(',
        '->all\s*\(',
        '->get\s*\(',
        '->first\s*\(',
        'DB::',
        'Db::',
    ];

    public function scan(array $files, string $root, array $options = []): array
    {
        $ctx = new CodeScanContext($root, 1048576, $options);
        $findings = [];

        foreach ($files as $file) {
            $lines = $ctx->readLines($file);
            $rel   = $ctx->relPath($file);

            // Ver por metodos: procurar loops aninhados
            for ($n = 1; $n <= count($lines); $n++) {
                $line = $lines[$n] ?? '';
                if ($ctx->isComment($line)) continue;

                // Inicio de loop exterior
                if (!preg_match('/\b(foreach|for|while)\b/', $line)) continue;

                // Procurar um segundo loop dentro dos proximos 20 linhas
                $depth = 1;
                $innerLoopLine = 0;
                $queryLine = 0;

                for ($i = $n + 1; $i < min($n + 20, count($lines) + 1); $i++) {
                    $l = $lines[$i] ?? '';

                    $depth += substr_count($l, '{');
                    $depth -= substr_count($l, '}');

                    if ($depth <= 0) break;

                    // Segundo loop?
                    if ($innerLoopLine === 0 && preg_match('/\b(foreach|for|while)\b/', $l)) {
                        $innerLoopLine = $i;
                    }

                    // Query?
                    if ($queryLine === 0) {
                        foreach ($this->dbPatterns as $pat) {
                            if (preg_match('/' . $pat . '/', $l)) {
                                $queryLine = $i;
                                break;
                            }
                        }
                    }
                }

                if ($innerLoopLine > 0 && $queryLine > 0) {
                    $findings[] = new Finding(
                        scanner:     $this->id(),
                        category:    $this->category(),
                        severity:    'medium',
                        title:       'Loop aninhado com query em ' . $rel . ':' . $n,
                        description: 'Loop dentro de loop com query de BD (linha ' . $queryLine . ') — complexidade O(n*m).',
                        table:       null,
                        column:      null,
                        impact:      'Com N e M grandes, executa N*M queries. Pode bloquear a BD por minutos.',
                        suggestion:  "Move a query para fora dos loops (batch):\n\n\$ids = [];\nforeach (\$grupos as \$g) {\n    foreach (\$g->itens as \$i) \$ids[] = \$i->id;\n}\n\$todos = \$repo->findByIds(\$ids);  // 1 query",
                        reference:   'https://en.wikipedia.org/wiki/Time_complexity',
                        data:        [
                            'file'           => $rel,
                            'line'           => $n,
                            'code'           => trim($line),
                            'inner_loop_line'=> $innerLoopLine,
                            'query_line'     => $queryLine,
                        ],
                    );
                }
            }
        }

        return $findings;
    }
}
