<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Scanners\Code;

use Beaver\Plugins\Frankei\Frankei\Code\CodeScannerInterface;
use Beaver\Plugins\Frankei\Frankei\Code\CodeScanContext;
use Beaver\Plugins\Frankei\Frankei\Finding;

class CodeTodoScanner implements CodeScannerInterface
{
    public function id(): string              { return 'code-todo'; }
    public function name(): string            { return 'Comentarios TODO/FIXME'; }
    public function category(): string        { return 'style'; }
    public function defaultSeverity(): string { return 'info'; }

    private const KEYWORDS = ['TODO', 'FIXME', 'HACK', 'XXX'];

    public function scan(array $files, string $root, array $options = []): array
    {
        $ctx = new CodeScanContext($root, 1048576, $options);
        $findings = [];

        foreach ($files as $file) {
            $lines = $ctx->readLines($file);
            $rel   = $ctx->relPath($file);

            foreach ($lines as $n => $line) {
                foreach (self::KEYWORDS as $kw) {
                    if (preg_match('/\b' . $kw . '\b/', $line)) {
                        // So alertar em comentarios
                        if (preg_match('#(//|#|\*|/\*)#', $line)) {
                            $findings[] = $this->finding($rel, $n, $line, $kw);
                            break;
                        }
                    }
                }
            }
        }

        return $findings;
    }

    private function finding(string $file, int $n, string $line, string $kw): Finding
    {
        return new Finding(
            scanner:     $this->id(),
            category:    $this->category(),
            severity:    'info',
            title:       $kw . ' em ' . $file . ':' . $n,
            description: 'Comentario pendente (' . $kw . ').',
            suggestion:  "Resolve ou remove o comentario antes de fechar a tarefa.",
            impact:      'Trabalho pendente acumula divida tecnica.',
            reference:   'https://en.wikipedia.org/wiki/Technical_debt',
            data:        ['file' => $file, 'line' => $n, 'code' => trim($line), 'keyword' => $kw],
        );
    }
}
