<?php

declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Scanners;

use Beaver\Plugins\Frankei\Frankei\Finding;
use Beaver\Plugins\Frankei\Frankei\ScanContext;
use Beaver\Plugins\Frankei\Frankei\ScannerInterface;

class ListIndexesScanner implements ScannerInterface
{
    public function id(): string
    {
        return 'list-indexes';
    }
    public function name(): string
    {
        return 'Índices';
    }
    public function category(): string
    {
        return 'structure';
    }
    public function defaultSeverity(): string
    {
        return 'info';
    }
    public function isReadOnly(): bool
    {
        return true;
    }

    public function run(ScanContext $ctx): array
    {
        if ($ctx->driver === 'sqlite') {
            return $this->runSqlite($ctx);
        }

        $schema = $ctx->driver === 'pgsql' ? 'public' : $ctx->database;
        $stmt = $ctx->pdo->prepare("
            SELECT TABLE_NAME, INDEX_NAME, COLUMN_NAME, NON_UNIQUE, SEQ_IN_INDEX
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = :schema
            ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX
        ");
        $stmt->execute([':schema' => $schema]);

        $byIndex = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            if (!$ctx->isTableIncluded($r['TABLE_NAME'])) {
                continue;
            }
            $key = $r['TABLE_NAME'] . '.' . $r['INDEX_NAME'];
            if (!isset($byIndex[$key])) {
                $byIndex[$key] = [
                    'table'   => $r['TABLE_NAME'],
                    'index'   => $r['INDEX_NAME'],
                    'unique'  => (int) $r['NON_UNIQUE'] === 0,
                    'columns' => [],
                ];
            }
            $byIndex[$key]['columns'][] = $r['COLUMN_NAME'];
        }

        $findings = [];
        foreach ($byIndex as $idx) {
            $findings[] = new Finding(
                scanner:     $this->id(),
                category:    $this->category(),
                severity:    'info',
                title:       $idx['table'] . '.' . $idx['index'],
                description: ($idx['unique'] ? 'UNIQUE' : 'INDEX') . ' (' . implode(', ', $idx['columns']) . ')',
                table:       $idx['table'],
                data:        $idx,
            );
        }
        return $findings;
    }

    private function runSqlite(ScanContext $ctx): array
    {
        $tables = $ctx->pdo->query("
            SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'
        ")->fetchAll(\PDO::FETCH_COLUMN);

        $findings = [];
        foreach ($tables as $t) {
            if (!$ctx->isTableIncluded($t)) {
                continue;
            }
            $rows = $ctx->pdo->query('PRAGMA index_list("' . str_replace('"', '""', $t) . '")')
                ->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $cols = $ctx->pdo->query('PRAGMA index_info("' . str_replace('"', '""', $r['name']) . '")')
                    ->fetchAll(\PDO::FETCH_ASSOC);
                $colNames = array_column($cols, 'name');
                $findings[] = new Finding(
                    scanner:     $this->id(),
                    category:    $this->category(),
                    severity:    'info',
                    title:       $t . '.' . $r['name'],
                    description: ((int) $r['unique'] === 1 ? 'UNIQUE' : 'INDEX') . ' (' . implode(', ', $colNames) . ')',
                    table:       $t,
                    data:        $r,
                );
            }
        }
        return $findings;
    }
}
