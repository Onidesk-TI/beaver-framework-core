<?php

declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Scanners;

use Beaver\Plugins\Frankei\Frankei\Finding;
use Beaver\Plugins\Frankei\Frankei\ScanContext;
use Beaver\Plugins\Frankei\Frankei\ScannerInterface;

class SuspiciousTypeScanner implements ScannerInterface
{
    public function id(): string
    {
        return 'suspicious-type';
    }
    public function name(): string
    {
        return 'Tipos suspeitos';
    }
    public function category(): string
    {
        return 'quality';
    }
    public function defaultSeverity(): string
    {
        return 'low';
    }
    public function isReadOnly(): bool
    {
        return true;
    }

    private const RULES = [
        ['pattern' => '/^(email|e_mail|mail)$/i', 'expected' => ['varchar','text'], 'reason' => 'Email deve ser VARCHAR/TEXT, não numérico'],
        ['pattern' => '/(^|_)(cc|nif|nipc|iban|nib|ssn|cpf|cnpj)($|_)/i', 'expected' => ['varchar','char','text'], 'reason' => 'IDs fiscais podem começar por 0 — não devem ser INT'],
        ['pattern' => '/^(data_|dt_|date_)/i', 'expected' => ['date','datetime','timestamp'], 'reason' => 'Coluna de data com tipo não-temporal'],
        ['pattern' => '/^(valor|preco|price|amount|total|montante)/i', 'expected' => ['decimal','numeric','float','double','int','bigint'], 'reason' => 'Valor monetário não deve ser TEXT'],
        ['pattern' => '/(password|senha|pwd|hash|token|secret)/i', 'expected' => ['varchar','char','binary','varbinary','text'], 'reason' => 'Password/hash deve ser string/binário'],
        ['pattern' => '/^(ativo|active|is_|has_|flag_|enabled)/i', 'expected' => ['tinyint','boolean','bool','bit','smallint'], 'reason' => 'Flag deveria ser booleano/tinyint'],
        ['pattern' => '/(observacoes|descricao|description|notas|notes)/i', 'expected' => ['text','varchar','mediumtext','longtext'], 'reason' => 'Campo de descrição — texto longo'],
    ];

    public function run(ScanContext $ctx): array
    {
        $columns = $this->getColumns($ctx);
        $findings = [];

        foreach ($columns as $c) {
            if (!$ctx->isTableIncluded($c['table'])) {
                continue;
            }

            $type = strtolower($c['type']);
            $baseType = preg_replace('/\(.*$/', '', $type);

            foreach (self::RULES as $rule) {
                if (!preg_match($rule['pattern'], $c['column'])) {
                    continue;
                }
                if (in_array($baseType, $rule['expected'], true)) {
                    continue;
                }

                $findings[] = new Finding(
                    scanner:     $this->id(),
                    category:    $this->category(),
                    severity:    'low',
                    title:       'Tipo suspeito: ' . $c['table'] . '.' . $c['column'] . ' (' . $type . ')',
                    description: $rule['reason'],
                    table:       $c['table'],
                    column:      $c['column'],
                    impact:      'Pode causar perda de dados ou comparações incorretas.',
                    suggestion:  'Rever se ' . $type . ' é o tipo mais adequado.',
                    data:        $c,
                );
                break;
            }
        }
        return $findings;
    }

    private function getColumns(ScanContext $ctx): array
    {
        $out = [];
        if ($ctx->driver === 'sqlite') {
            $tables = $ctx->pdo->query("
                SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'
            ")->fetchAll(\PDO::FETCH_COLUMN);
            foreach ($tables as $t) {
                $cols = $ctx->pdo->query('PRAGMA table_info("' . str_replace('"', '""', $t) . '")')
                    ->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($cols as $c) {
                    $out[] = ['table' => $t, 'column' => $c['name'], 'type' => $c['type']];
                }
            }
            return $out;
        }

        $schema = $ctx->driver === 'pgsql' ? 'public' : $ctx->database;
        $stmt = $ctx->pdo->prepare("
            SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, COLUMN_TYPE
            FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :schema
        ");
        $stmt->execute([':schema' => $schema]);
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $out[] = [
                'table'  => $r['TABLE_NAME'],
                'column' => $r['COLUMN_NAME'],
                'type'   => $r['COLUMN_TYPE'] ?: $r['DATA_TYPE'],
            ];
        }
        return $out;
    }
}
