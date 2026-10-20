<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Code;

use Beaver\Plugins\Frankei\Frankei\Finding;

interface CodeScannerInterface
{
    public function id(): string;
    public function name(): string;
    public function category(): string;
    public function defaultSeverity(): string;

    /**
     * Corre o scanner sobre uma lista de ficheiros.
     *
     * @param string[] $files   Lista de caminhos absolutos de ficheiros .php
     * @param string   $root    Path raiz do projeto (para paths relativos)
     * @param array    $options Opções do utilizador (skipXxx, runXxx, etc.)
     * @return Finding[]
     */
    public function scan(array $files, string $root, array $options = []): array;
}
