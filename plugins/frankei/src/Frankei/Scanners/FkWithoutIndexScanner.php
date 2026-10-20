<?php

declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Scanners;

use Beaver\Plugins\Frankei\Frankei\Finding;
use Beaver\Plugins\Frankei\Frankei\ScanContext;
use Beaver\Plugins\Frankei\Frankei\ScannerInterface;

class FkWithoutIndexScanner implements ScannerInterface
{
    public function id(): string
    {
        return 'fk-without-index';
    }
    public function name(): string
    {
        return 'FKs sem índice';
    }
    public function category(): string
    {
        return 'quality';
    }
    public function defaultSeverity(): string
    {
        return 'medium';
    }
    public function isReadOnly(): bool
    {
        return true;
    }

    public function run(ScanContext $ctx): array
    {
        if ($ctx->driver === 'sqlite') {
            return [];
        }

        $schema = $ctx->driver === 'pgsql' ? 'public' : $ctx->database;

        $fks = $ctx->pdo->prepare("
            SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME, CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = :schema AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $fks->execute([':schema' => $schema]);

        $idx = $ctx->pdo->prepare("
            SELECT DISTINCT TABLE_NAME, COLUMN_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = :schema AND SEQ_IN_INDEX = 1
        ");
        $idx->execute([':schema' => $schema]);

        $indexed = [];
        foreach ($idx->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $indexed[$r['TABLE_NAME'] . '.' . $r['COLUMN_NAME']] = true;
        }

        $findings = [];
        foreach ($fks->fetchAll(\PDO::FETCH_ASSOC) as $fk) {
            if (!$ctx->isTableIncluded($fk['TABLE_NAME'])) {
                continue;
            }

            $key = $fk['TABLE_NAME'] . '.' . $fk['COLUMN_NAME'];
            if (isset($indexed[$key])) {
                continue;
            }

            $findings[] = new Finding(
                scanner:     $this->id(),
                category:    $this->category(),
                severity:    'medium',
                title:       'FK sem índice: ' . $fk['TABLE_NAME'] . '.' . $fk['COLUMN_NAME'],
                description: 'A coluna ' . $fk['COLUMN_NAME'] . ' tem FK mas não está indexada.',
                table:       $fk['TABLE_NAME'],
                column:      $fk['COLUMN_NAME'],
                impact:      'DELETE/UPDATE na tabela referenciada faz full scan da filha. JOINs lentos.',
                suggestion:  'CREATE INDEX `idx_' . $fk['TABLE_NAME'] . '_' . $fk['COLUMN_NAME'] . "`\n  ON `" . $fk['TABLE_NAME'] . "` (`" . $fk['COLUMN_NAME'] . '`);',
                reference:   'https://dev.mysql.com/doc/refman/8.0/en/create-index.html',
                data:        $fk,
            );
        }
        return $findings;
    }
}
