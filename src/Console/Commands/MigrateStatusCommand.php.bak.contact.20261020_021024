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

class MigrateStatusCommand extends Command
{
    use HasMigrationsPaths;

    protected string $signature = 'migrate:status';
    protected string $description = 'Show migration status';

    public function handle(): int
    {
        $paths = $this->migrationsPaths();

        if ($paths === []) {
            $this->error('Nenhuma pasta de migrations encontrada.');
            return self::FAILURE;
        }

        $migrator = new Migrator($paths);
        $status   = $migrator->status();

        if ($status === []) {
            $this->info('Nenhuma migration encontrada.');
            return self::SUCCESS;
        }

        $this->line('  Estado  Migration');
        $this->line('  ------  ------------------------------------------');

        foreach ($status as $s) {
            $marker = $s['ran'] ? '✓' : '·';
            $this->line(sprintf('  %s       %s', $marker, $s['migration']));
        }

        return self::SUCCESS;
    }
}
