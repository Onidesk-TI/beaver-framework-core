<?php

/**
 * Beaver Framework — Modern PHP framework with a plugin ecosystem.
 *
 * @package    Beaver Framework
 * @author     Franco <onidesk@outlook.com>
 * @copyright  2026 Onidesk
 * @license    MIT <https://opensource.org/licenses/MIT>
 * @link       https://github.com/Onidesk-TI/beaver-framework
 */

namespace Beaver\Console\Commands;

use Beaver\Console\Command;
use Beaver\Console\Commands\Concerns\HasMigrationsPaths;
use Beaver\Database\Migrations\Migrator;

class MigrateRollbackCommand extends Command
{
    use HasMigrationsPaths;

    protected string $signature = 'migrate:rollback {--steps=1}';
    protected string $description = 'Rollback the last batch of migrations';

    public function handle(): int
    {
        $paths = $this->migrationsPaths();
        $steps = (int) $this->option('steps', 1);

        if ($steps < 1) {
            $steps = 1;
        }

        if ($paths === []) {
            $this->error('Nenhuma pasta de migrations encontrada.');
            return self::FAILURE;
        }

        $migrator = new Migrator($paths);

        try {
            $reverted = $migrator->rollback($steps);
        } catch (\Throwable $e) {
            $this->error('Erro a fazer rollback: ' . $e->getMessage());
            return self::FAILURE;
        }

        if ($reverted === []) {
            $this->info('Nada a reverter.');
            return self::SUCCESS;
        }

        $this->success(count($reverted) . ' migration(s) revertida(s):');
        $this->line();
        foreach ($reverted as $name) {
            $this->line('  ↩ ' . $name);
        }

        return self::SUCCESS;
    }
}
