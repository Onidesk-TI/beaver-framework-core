<?php

declare(strict_types=1);

namespace Beaver\Console\Commands;

use Beaver\Console\Command;
use Beaver\Database\Database;

class DbWipeCommand extends Command
{
    protected string $signature = 'db:wipe {--force}';
    protected string $description = 'Apaga TODAS as tabelas da base de dados';

    public function handle(): int
    {
        if (!$this->option('force')) {
            $this->warning('⚠️  Isto vai APAGAR TODAS as tabelas.');
            if (!$this->confirm('Tens a certeza?', false)) {
                $this->info('Cancelado.');
                return self::SUCCESS;
            }
        }

        $db = Database::getInstance();
        $driver = $db->getDriver();

        $tables = $this->listTables($db, $driver);

        if ($tables === []) {
            $this->info('Não há tabelas para apagar.');
            return self::SUCCESS;
        }

        // Desativar foreign keys temporariamente
        if ($driver === 'sqlite') {
            $db->query('PRAGMA foreign_keys = OFF');
        } elseif ($driver === 'mysql') {
            $db->query('SET FOREIGN_KEY_CHECKS = 0');
        }

        foreach ($tables as $table) {
            try {
                $db->query("DROP TABLE IF EXISTS \"{$table}\"");
                $this->line("  ✓ {$table} apagada");
            } catch (\Throwable $e) {
                $this->error("  ✖ {$table}: " . $e->getMessage());
            }
        }

        // Reativar foreign keys
        if ($driver === 'sqlite') {
            $db->query('PRAGMA foreign_keys = ON');
        } elseif ($driver === 'mysql') {
            $db->query('SET FOREIGN_KEY_CHECKS = 1');
        }

        $this->line();
        $this->success(count($tables) . ' tabela(s) apagada(s).');

        return self::SUCCESS;
    }

    private function listTables(Database $db, string $driver): array
    {
        if ($driver === 'sqlite') {
            $rows = $db->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            return array_column($rows, 'name');
        }

        if ($driver === 'mysql') {
            $rows = $db->select('SHOW TABLES');
            return array_map(fn($r) => array_values($r)[0], $rows);
        }

        if ($driver === 'pgsql') {
            $rows = $db->select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
            return array_column($rows, 'tablename');
        }

        return [];
    }
}
