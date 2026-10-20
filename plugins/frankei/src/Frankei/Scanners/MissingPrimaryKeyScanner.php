<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Scanners;

use Beaver\Plugins\Frankei\Frankei\Finding;
use Beaver\Plugins\Frankei\Frankei\ScanContext;
use Beaver\Plugins\Frankei\Frankei\ScannerInterface;

class MissingPrimaryKeyScanner implements ScannerInterface
{
    public function id(): string              { return 'missing-pk'; }
    public function name(): string            { return 'Tabelas sem PRIMARY KEY'; }
    public function category(): string        { return 'quality'; }
    public function defaultSeverity(): string { return 'high'; }
    public function isReadOnly(): bool        { return true; }

    public function run(ScanContext $ctx): array
    {
        $tables = $this->getTables($ctx);
        $withPk = [];
        foreach ($this->getPks($ctx) as $t) $withPk[$t] = true;

        $findings = [];
        foreach ($tables as $t) {
            if (!$ctx->isTableIncluded($t)) continue;
            if (isset($withPk[$t])) continue;

            $findings[] = new Finding(
                scanner:     $this->id(),
                category:    $this->category(),
                severity:    'high',
                title:       'Tabela sem PRIMARY KEY: ' . $t,
                description: 'A tabela ' . $t . ' não tem chave primária.',
                table:       $t,
                impact:      'Replicação MySQL falha, ORMs não conseguem mapear, UPDATEs em massa ficam ambíguos.',
                suggestion:  'ALTER TABLE `' . $t . "`\n  ADD COLUMN `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;",
                reference:   'https://dev.mysql.com/doc/refman/8.0/en/example-auto-increment.html',
            );
        }
        return $findings;
    }

    private function getTables(ScanContext $ctx): array
    {
        if ($ctx->driver === 'sqlite') {
            return $ctx->pdo->query("
                SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'
            ")->fetchAll(\PDO::FETCH_COLUMN);
        }
        $schema = $ctx->driver === 'pgsql' ? 'public' : $ctx->database;
        $stmt = $ctx->pdo->prepare("
            SELECT TABLE_NAME FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = :schema AND TABLE_TYPE = 'BASE TABLE'
        ");
        $stmt->execute([':schema' => $schema]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function getPks(ScanContext $ctx): array
    {
        if ($ctx->driver === 'sqlite') {
            $out = [];
            foreach ($this->getTables($ctx) as $t) {
                $cols = $ctx->pdo->query('PRAGMA table_info("' . str_replace('"', '""', $t) . '")')
                    ->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($cols as $c) {
                    if ((int) $c['pk'] > 0) { $out[] = $t; break; }
                }
            }
            return $out;
        }
        $schema = $ctx->driver === 'pgsql' ? 'public' : $ctx->database;
        $stmt = $ctx->pdo->prepare("
            SELECT DISTINCT TABLE_NAME FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = :schema AND CONSTRAINT_TYPE = 'PRIMARY KEY'
        ");
        $stmt->execute([':schema' => $schema]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
}
