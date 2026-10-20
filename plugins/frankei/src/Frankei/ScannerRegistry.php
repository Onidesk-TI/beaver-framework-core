<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei;

use Beaver\Plugins\Frankei\Frankei\Scanners\ListTablesScanner;
use Beaver\Plugins\Frankei\Frankei\Scanners\ListColumnsScanner;
use Beaver\Plugins\Frankei\Frankei\Scanners\ListForeignKeysScanner;
use Beaver\Plugins\Frankei\Frankei\Scanners\ListIndexesScanner;
use Beaver\Plugins\Frankei\Frankei\Scanners\MissingPrimaryKeyScanner;
use Beaver\Plugins\Frankei\Frankei\Scanners\FkWithoutIndexScanner;
use Beaver\Plugins\Frankei\Frankei\Scanners\SuspiciousTypeScanner;
use Beaver\Plugins\Frankei\Frankei\Scanners\ExcessiveNullableScanner;

class ScannerRegistry
{
    /** @var array<string, ScannerInterface> */
    private array $scanners = [];

    public function __construct()
    {
        $this->register(new ListTablesScanner());
        $this->register(new ListColumnsScanner());
        $this->register(new ListForeignKeysScanner());
        $this->register(new ListIndexesScanner());
        $this->register(new MissingPrimaryKeyScanner());
        $this->register(new FkWithoutIndexScanner());
        $this->register(new SuspiciousTypeScanner());
        $this->register(new ExcessiveNullableScanner());
    }

    public function register(ScannerInterface $scanner): void
    {
        $this->scanners[$scanner->id()] = $scanner;
    }

    public function byId(string $id): ?ScannerInterface
    {
        return $this->scanners[$id] ?? null;
    }

    public function byCategory(string $category): array
    {
        return array_filter($this->scanners, fn($s) => $s->category() === $category);
    }

    /** @return array<string, ScannerInterface> */
    public function all(): array
    {
        return $this->scanners;
    }

    public function metadata(): array
    {
        $out = [];
        foreach ($this->scanners as $id => $s) {
            $out[] = [
                'id'               => $id,
                'name'             => $s->name(),
                'category'         => $s->category(),
                'default_severity' => $s->defaultSeverity(),
                'read_only'        => $s->isReadOnly(),
            ];
        }
        return $out;
    }
}
