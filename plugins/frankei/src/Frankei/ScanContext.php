<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei;

class ScanContext
{
    public function __construct(
        public \PDO $pdo,
        public string $driver,
        public string $database,
        public array $tables = [],
        public string $mode = 'readonly',
    ) {}

    public function hasTableFilter(): bool
    {
        return !empty($this->tables);
    }

    public function isTableIncluded(string $table): bool
    {
        if (!$this->hasTableFilter()) return true;
        return in_array($table, $this->tables, true);
    }
}
