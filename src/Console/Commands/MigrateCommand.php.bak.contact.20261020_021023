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

class MigrateCommand extends Command
{
    use HasMigrationsPaths;

    protected string $signature = 'migrate';
    protected string $description = 'Run pending database migrations';

    public function handle(): int
    {
        $paths = $this->migrationsPaths();

        if ($paths === []) {
            $this->error('Nenhuma pasta de migrations encontrada.');
            return self::FAILURE;
        }

        $migrator = new Migrator($paths);

        try {
            $executed = $migrator->run();
        } catch (\Throwable $e) {
            $this->error('Erro a correr migrations: ' . $e->getMessage());
            return self::FAILURE;
        }

        if ($executed === []) {
            $this->info('Nada a correr. Todas as migrations já foram aplicadas.');
            return self::SUCCESS;
        }

        $this->success(count($executed) . ' migration(s) corrida(s):');
        $this->line();
        foreach ($executed as $name) {
            $this->line('  ✓ ' . $name);
        }

        return self::SUCCESS;
    }

    // ---------- plugin paths (SDK) ----------

    /** @var array<string,string> */
    private static array $pluginPaths = [];

    /**
     * Registra uma pasta de migrations de um plugin.
     * Chamado por PluginBase::loadMigrations().
     */
    public static function addPluginPath(string $slug, string $path): void
    {
        self::$pluginPaths[$slug] = rtrim($path, '/');
    }

    /** @return array<string,string> */
    public static function pluginPaths(): array
    {
        return self::$pluginPaths;
    }
}
