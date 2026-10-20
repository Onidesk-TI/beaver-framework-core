<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Scanners;

use Beaver\Plugins\Frankei\Frankei\Finding;
use Beaver\Plugins\Frankei\Frankei\ScanContext;
use Beaver\Plugins\Frankei\Frankei\ScannerInterface;

class ExcessiveNullableScanner implements ScannerInterface
{
    private const THRESHOLD = 0.5;

    public function id(): string              { return 'excessive-nullable'; }
    public function name(): string            { return 'Colunas nullable em excesso'; }
    public function category(): string        { return 'quality'; }
    public function defaultSeverity(): string { return 'low'; }
    public function isReadOnly(): bool        { return true; }

    public function run(ScanContext $ctx): array
    {
        $byTable = $this->getColumnsByTable($ctx);
        $findings = [];

        foreach ($byTable as $table => $cols) {
            if (!$ctx->isTableIncluded($table)) continue;

            $total = count($cols);
            if ($total < 3) continue;

            $nullable = array_filter($cols, fn($c) => $c['nullable']);
            $nNullable = count($nullable);
            $ratio = $nNullable / $total;

            if ($ratio < self::THRESHOLD) continue;

            $names = array_map(fn($c) => $c['column'], $nullable);
            $sample = array_slice($names, 0, 5);
            $extra = count($names) - count($sample);

            $findings[] = new Finding(
                scanner:     $this->id(),
                category:    $this->category(),
                severity:    'low',
                title:       'Nullable em excesso: ' . $table,
                description: $nNullable . ' de ' . $total . ' colunas nullable (' . round($ratio * 100) . '%)',
                table:       $table,
                impact:      'Modelo frágil — valores em falta escondem bugs de negócio.',
                suggestion:  'Rever quais colunas fazem sentido serem NULL. Ex.: ' .
                             implode(', ', $sample) . ($extra > 0 ? ' e mais ' . $extra : ''),
                data: [
                    'total_columns'    => $total,
                    'nullable_columns' => $nNullable,
                    'ratio'            => round($ratio, 2),
                    'nullable_names'   => $names,
                ],
            );
        }
        return $findings;
    }

    private function getColumnsByTable(ScanContext $ctx): array
    {
        $out = [];
        if ($ctx->driver === 'sqlite') {
            $tables = $ctx->pdo->query("
                SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'
            ")->fetchAll(\PDO::FETCH_COLUMN);
            foreach ($tables as $t) {
                $cols = $ctx->pdo->query('PRAGMA table_info("' . str_replace('"', '""', $t) . '")')
                    ->fetchAll(\PDO::FETCH_ASSOC);
                $out[$t] = array_map(fn($c) => [
                    'column'   => $c['name'],
                    'nullable' => (int) $c['notnull'] === 0 && (int) $c['pk'] === 0,
                ], $cols);
            }
            return $out;
        }

        $schema = $ctx->driver === 'pgsql' ? 'public' : $ctx->database;
        $stmt = $ctx->pdo->prepare("
            SELECT TABLE_NAME, COLUMN_NAME, IS_NULLABLE, COLUMN_KEY
            FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :schema
        ");
        $stmt->execute([':schema' => $schema]);
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $out[$r['TABLE_NAME']][] = [
                'column'   => $r['COLUMN_NAME'],
                'nullable' => $r['IS_NULLABLE'] === 'YES',
            ];
        }
        return $out;
    }
}
