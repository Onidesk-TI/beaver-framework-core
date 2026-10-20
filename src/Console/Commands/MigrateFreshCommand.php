<?php

declare(strict_types=1);

namespace Beaver\Console\Commands;

use Beaver\Console\Command;
use Beaver\Console\Commands\Concerns\HasMigrationsPaths;
use Beaver\Database\Migrations\Migrator;

class MigrateFreshCommand extends Command
{
    use HasMigrationsPaths;

    protected string $signature = 'migrate:fresh {--seed}';
    protected string $description = 'Apaga todas as tabelas e corre as migrations de novo';

    public function handle(): int
    {
        $paths = $this->migrationsPaths();

        if ($paths === []) {
            $this->error('Nenhuma pasta de migrations encontrada.');
            return self::FAILURE;
        }

        $migrator = new Migrator($paths);

        $this->warning('A apagar todas as tabelas...');

        try {
            // Rollback total
            $migrator->rollback(PHP_INT_MAX);
        } catch (\Throwable $e) {
            $this->error('Erro no rollback: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->success('Tabelas apagadas.');
        $this->line();

        // Migrate de novo
        try {
            $executed = $migrator->run();
        } catch (\Throwable $e) {
            $this->error('Erro a correr migrations: ' . $e->getMessage());
            return self::FAILURE;
        }

        foreach ($executed as $name) {
            $this->line("  ✓ {$name}");
        }

        $this->line();
        $this->success(count($executed) . ' migration(s) corrida(s).');

        // Se --seed, corre seeders
        if ($this->option('seed')) {
            $this->line();
            $this->info('A correr seeders...');
            return (new DbSeedCommand())->handle();
        }

        return self::SUCCESS;
    }
}
