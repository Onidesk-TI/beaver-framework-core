<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei;

interface ScannerInterface
{
    public function id(): string;
    public function name(): string;
    public function category(): string;
    public function defaultSeverity(): string;
    public function isReadOnly(): bool;

    /** @return Finding[] */
    public function run(ScanContext $ctx): array;
}
