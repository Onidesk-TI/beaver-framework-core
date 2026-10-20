<?php

declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Scanners;

use Beaver\Plugins\Frankei\Frankei\Finding;
use Beaver\Plugins\Frankei\Frankei\ScanContext;
use Beaver\Plugins\Frankei\Frankei\ScannerInterface;

class ListColumnsScanner implements ScannerInterface
{
    public function id(): string
    {
        return 'list-columns';
    }
    public function name(): string
    {
        return 'Colunas por tabela';
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
        $findings = [];

        if ($ctx->driver === 'sqlite') {
            $tables = $ctx->pdo->query("
                SELECT name FROM sqlite_master
                WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name
            ")->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($tables as $t) {
                if (!$ctx->isTableIncluded($t)) {
                    continue;
                }
                $stmt = $ctx->pdo->query('PRAGMA table_info("' . str_replace('"', '""', $t) . '")');
                foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $c) {
                    $findings[] = $this->makeFinding($t, [
                        'name'     => $c['name'],
                        'type'     => $c['type'],
                        'nullable' => (int) $c['notnull'] === 0,
                        'default'  => $c['dflt_value'],
                        'key'      => (int) $c['pk'] > 0 ? 'PRI' : '',
                        'extra'    => '',
                    ]);
                }
            }
        } else {
            $schema = $ctx->driver === 'pgsql' ? 'public' : $ctx->database;
            $stmt = $ctx->pdo->prepare("
                SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, COLUMN_TYPE,
                       IS_NULLABLE, COLUMN_DEFAULT, COLUMN_KEY, EXTRA
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = :schema
                ORDER BY TABLE_NAME, ORDINAL_POSITION
            ");
            $stmt->execute([':schema' => $schema]);

            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $c) {
                if (!$ctx->isTableIncluded($c['TABLE_NAME'])) {
                    continue;
                }
                $findings[] = $this->makeFinding($c['TABLE_NAME'], [
                    'name'     => $c['COLUMN_NAME'],
                    'type'     => $c['COLUMN_TYPE'] ?: $c['DATA_TYPE'],
                    'nullable' => $c['IS_NULLABLE'] === 'YES',
                    'default'  => $c['COLUMN_DEFAULT'],
                    'key'      => $c['COLUMN_KEY'] ?? '',
                    'extra'    => $c['EXTRA'] ?? '',
                ]);
            }
        }
        return $findings;
    }

    private function makeFinding(string $table, array $c): Finding
    {
        $attrs = [];
        if (!empty($c['nullable'])) {
            $attrs[] = 'NULL';
        }
        if (($c['key'] ?? '') === 'PRI') {
            $attrs[] = 'PK';
        }
        if (($c['key'] ?? '') === 'UNI') {
            $attrs[] = 'UNIQUE';
        }
        if (($c['key'] ?? '') === 'MUL') {
            $attrs[] = 'INDEX';
        }
        if (!empty($c['extra'])) {
            $attrs[] = strtoupper($c['extra']);
        }
        if (isset($c['default']) && $c['default'] !== null) {
            $attrs[] = 'DEFAULT ' . $c['default'];
        }

        return new Finding(
            scanner:     $this->id(),
            category:    $this->category(),
            severity:    'info',
            title:       $table . '.' . $c['name'],
            description: $c['type'] . (empty($attrs) ? '' : ' [' . implode(', ', $attrs) . ']'),
            table:       $table,
            column:      $c['name'],
            data:        $c,
        );
    }
}
