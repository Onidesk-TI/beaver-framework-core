<?php

declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Scanners;

use Beaver\Plugins\Frankei\Frankei\Finding;
use Beaver\Plugins\Frankei\Frankei\ScanContext;
use Beaver\Plugins\Frankei\Frankei\ScannerInterface;

class ListForeignKeysScanner implements ScannerInterface
{
    public function id(): string
    {
        return 'list-fks';
    }
    public function name(): string
    {
        return 'Foreign keys';
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
        $rows = match ($ctx->driver) {
            'sqlite' => $this->runSqlite($ctx),
            'pgsql'  => $this->runPgsql($ctx),
            default  => $this->runMysql($ctx),
        };

        $findings = [];
        foreach ($rows as $r) {
            if (!$ctx->isTableIncluded($r['table'])) {
                continue;
            }

            $findings[] = new Finding(
                scanner:     $this->id(),
                category:    $this->category(),
                severity:    'info',
                title:       $r['table'] . '.' . $r['column'] . ' → ' . $r['ref_table'] . '.' . $r['ref_column'],
                description: 'Foreign key' . (!empty($r['constraint']) ? ' (' . $r['constraint'] . ')' : ''),
                table:       $r['table'],
                column:      $r['column'],
                data:        $r,
            );
        }
        return $findings;
    }

    private function runMysql(ScanContext $ctx): array
    {
        $stmt = $ctx->pdo->prepare("
            SELECT kcu.TABLE_NAME AS `table`, kcu.COLUMN_NAME AS `column`,
                   kcu.REFERENCED_TABLE_NAME AS ref_table,
                   kcu.REFERENCED_COLUMN_NAME AS ref_column,
                   kcu.CONSTRAINT_NAME AS `constraint`
            FROM information_schema.KEY_COLUMN_USAGE kcu
            WHERE kcu.TABLE_SCHEMA = :db AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
            ORDER BY kcu.TABLE_NAME, kcu.COLUMN_NAME
        ");
        $stmt->execute([':db' => $ctx->database]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function runPgsql(ScanContext $ctx): array
    {
        $stmt = $ctx->pdo->query("
            SELECT tc.table_name AS \"table\", kcu.column_name AS \"column\",
                   ccu.table_name AS ref_table, ccu.column_name AS ref_column,
                   tc.constraint_name AS \"constraint\"
            FROM information_schema.table_constraints tc
            JOIN information_schema.key_column_usage kcu ON tc.constraint_name = kcu.constraint_name
            JOIN information_schema.constraint_column_usage ccu ON ccu.constraint_name = tc.constraint_name
            WHERE tc.constraint_type = 'FOREIGN KEY' AND tc.table_schema = 'public'
            ORDER BY tc.table_name, kcu.column_name
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function runSqlite(ScanContext $ctx): array
    {
        $tables = $ctx->pdo->query("
            SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'
        ")->fetchAll(\PDO::FETCH_COLUMN);

        $out = [];
        foreach ($tables as $t) {
            $rows = $ctx->pdo->query('PRAGMA foreign_key_list("' . str_replace('"', '""', $t) . '")')
                ->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $out[] = [
                    'table' => $t, 'column' => $r['from'],
                    'ref_table' => $r['table'], 'ref_column' => $r['to'],
                    'constraint' => null,
                ];
            }
        }
        return $out;
    }
}
