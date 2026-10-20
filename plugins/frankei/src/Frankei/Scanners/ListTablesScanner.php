<?php

declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Scanners;

use Beaver\Plugins\Frankei\Frankei\Finding;
use Beaver\Plugins\Frankei\Frankei\ScanContext;
use Beaver\Plugins\Frankei\Frankei\ScannerInterface;

class ListTablesScanner implements ScannerInterface
{
    public function id(): string
    {
        return 'list-tables';
    }
    public function name(): string
    {
        return 'Lista de tabelas';
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
            'sqlite' => $ctx->pdo->query("
                SELECT name, type FROM sqlite_master
                WHERE type='table' AND name NOT LIKE 'sqlite_%'
                ORDER BY name
            ")->fetchAll(\PDO::FETCH_ASSOC),
            'pgsql'  => $ctx->pdo->query("
                SELECT table_name AS name, table_type AS type
                FROM information_schema.tables
                WHERE table_schema='public' ORDER BY table_name
            ")->fetchAll(\PDO::FETCH_ASSOC),
            default  => $ctx->pdo->query("
                SELECT TABLE_NAME AS name, TABLE_TYPE AS type
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME
            ")->fetchAll(\PDO::FETCH_ASSOC),
        };

        $findings = [];
        foreach ($rows as $r) {
            $table = $r['name'];
            if (!$ctx->isTableIncluded($table)) {
                continue;
            }

            $findings[] = new Finding(
                scanner:     $this->id(),
                category:    $this->category(),
                severity:    'info',
                title:       $table,
                description: 'Tabela ' . ($r['type'] ?? 'BASE TABLE'),
                table:       $table,
                data:        ['type' => $r['type'] ?? null],
            );
        }
        return $findings;
    }
}
