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

namespace Beaver\Console\Commands\Concerns;

/**
 * Recolhe os paths de migrations de:
 *   - framework/database/migrations
 *   - <plugin>/database/migrations para cada plugin ativo
 *
 * Uso nos comandos:
 *
 *   class MigrateCommand extends Command
 *   {
 *       use HasMigrationsPaths;
 *
 *       public function handle(): int
 *       {
 *           $paths = $this->migrationsPaths();
 *           $migrator = new Migrator($paths);
 *           // ...
 *       }
 *   }
 */
trait HasMigrationsPaths
{
    /** @return string[] */
    protected function migrationsPaths(): array
    {
        $paths = [];

        // 1. Framework
        $frameworkPath = rtrim($this->projectPath, '/') . '/database/migrations';
        if (is_dir($frameworkPath)) {
            $paths[] = $frameworkPath;
        }

        // 2. Plugins (dev + prod)
        try {
            $app = \Beaver\Foundation\Application::getInstance();
            $pm  = $app->make(\Beaver\Plugin\PluginManager::class);
            $pm->discover();

            foreach ($pm->manifests() as $manifest) {
                $pluginMigrations = rtrim($manifest['path'] ?? '', '/') . '/database/migrations';

                if (is_dir($pluginMigrations) && !in_array($pluginMigrations, $paths, true)) {
                    $paths[] = $pluginMigrations;
                }
            }
        } catch (\Throwable) {
            // Sem plugins ou sem config — ignora
        }

        return $paths;
    }
}
