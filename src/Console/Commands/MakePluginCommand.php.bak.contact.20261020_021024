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

class MakePluginCommand extends Command
{
    protected string $signature = 'make:plugin {name} {--prod} {--dest=} {--author=}';
    protected string $description = 'Create a new plugin';

    public function handle(): int
    {
        $name = (string) $this->argument('name', '');

        if ($name === '') {
            $this->error('Falta o nome do plugin.');
            $this->line('Uso: php beaver make:plugin <nome>');
            return self::INVALID;
        }

        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '_', $slug) ?? '';
        $slug = trim($slug, '_');

        if ($slug === '') {
            $this->error('Nome inválido após normalização.');
            return self::INVALID;
        }

        $studly = str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $slug)));

        // Caminho base
        $basePath = null;

        $dest = $this->option('dest');
        if (is_string($dest) && $dest !== '') {
            $basePath = rtrim($dest, '/');
        } else {
            try {
                $app = \Beaver\Foundation\Application::getInstance();
                $basePath = $app->config('app.plugins.dev_path');
                $basePath = rtrim((string) $basePath, '/');
            } catch (\Throwable) {
                $basePath = null;
            }
        }

        if ($basePath === null || $basePath === '') {
            $basePath = dirname($this->projectPath, 1) . '/beaver-plugins';
        }

        $pluginPath = $basePath . '/' . $slug;

        if (is_dir($pluginPath)) {
            $this->warning("Já existe: {$pluginPath}");

            if (!$this->confirm('Deseja recriar?')) {
                $this->info('Cancelado.');
                return self::FAILURE;
            }

            $this->deleteDirectory($pluginPath);
            $this->info('Apagado.');
        }

        // Criar estrutura
        $dirs = [
            '', 'lang', 'routes', 'resources/views',
            'src', 'src/Controllers', 'src/Models', 'src/Services',
            'database/migrations', 'storage',
        ];

        foreach ($dirs as $dir) {
            $path = $dir === '' ? $pluginPath : $pluginPath . '/' . $dir;
            @mkdir($path, 0775, true);
        }

        foreach (
            ['resources/views', 'database/migrations', 'storage',
                  'src/Controllers', 'src/Models', 'src/Services'] as $dir
        ) {
            @touch($pluginPath . '/' . $dir . '/.gitkeep');
        }

        $author = (string) ($this->option('author') ?: 'Onidesk');

        file_put_contents($pluginPath . '/plugin.json', $this->pluginJson($studly, $slug, $author));
        file_put_contents($pluginPath . '/composer.json', $this->composerJson($slug, $studly));
        file_put_contents($pluginPath . '/.gitignore', $this->gitignore());
        file_put_contents($pluginPath . '/README.md', "# {$studly} Plugin\n\nPlugin {$studly} para o Beaver Framework.\n");
        file_put_contents($pluginPath . '/NOTES.md', "# {$studly} Plugin — notas de design\n\nGerado por make:plugin.\n");
        file_put_contents($pluginPath . '/src/' . $studly . 'Plugin.php', $this->pluginClass($studly));
        file_put_contents($pluginPath . '/lang/pt.php', "<?php\n\nreturn [\n    'title' => '{$studly}',\n];\n");
        file_put_contents($pluginPath . '/lang/en.php', "<?php\n\nreturn [\n    'title' => '{$studly}',\n];\n");
        file_put_contents($pluginPath . '/routes/web.php', $this->routes($studly, $slug));

        $this->line();
        $this->success("Plugin criado: {$slug}");
        $this->line("     {$pluginPath}");

        return self::SUCCESS;
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($dir);
    }

    private function pluginJson(string $studly, string $slug, string $author): string
    {
        return json_encode([
            'name'           => $studly,
            'slug'           => $slug,
            'version'        => '0.1.0',
            'author'         => $author,
            'description'    => "Plugin {$studly} para Beaver Framework",
            'namespace'      => 'Beaver\\Plugins\\' . $studly,
            'main'           => 'src/' . $studly . 'Plugin.php',
            'beaver_version' => '>=0.1.0',
            'requires'       => ['php' => '>=8.1'],
            'admin_menu'     => ['label' => $studly, 'icon' => 'fa-cube'],
            'routes'         => 'routes/web.php',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }

    private function composerJson(string $slug, string $studly): string
    {
        return json_encode([
            'name'        => "onidesk/beaver-{$slug}",
            'description' => "Plugin {$slug} para Beaver Framework",
            'type'        => 'beaver-plugin',
            'license'     => 'proprietary',
            'require'     => ['php' => '>=8.1'],
            'autoload'    => [
                'psr-4' => [
                    'Beaver\\Plugins\\' . $studly . '\\' => 'src/',
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }

    private function gitignore(): string
    {
        return "/vendor/\n/.env\n/.env.local\n\n*.bak\n*.bak-*\n*.sqlite\n*.sqlite-*\n\n.DS_Store\nThumbs.db\n*.swp\n*~\n*:Zone.Identifier\n";
    }

    private function pluginClass(string $studly): string
    {
        return <<<PHP
<?php

namespace Beaver\\Plugins\\{$studly};

use Beaver\\Plugin\\PluginBase;

class {$studly}Plugin extends PluginBase
{
    public function boot(): void
    {
        \$this->loadViews();
        \$this->loadTranslations();
        \$this->loadRoutes();

        error_log("[{$studly}] carregado v{\$this->version()} de {\$this->path}");
    }
}
PHP;
    }

    private function routes(string $studly, string $slug): string
    {
        return <<<PHP
<?php
/** @var \\Beaver\\Http\\Router \$router */
/** @var \\Beaver\\Plugins\\{$studly}\\{$studly}Plugin \$plugin */

use Beaver\\Http\\Response;

\$router->get('/{$slug}', function () use (\$plugin) {
    return Response::json([
        'plugin'  => \$plugin->name(),
        'version' => \$plugin->version(),
        'slug'    => \$plugin->slug(),
        'path'    => \$plugin->path,
        'source'  => 'dev',
    ]);
});
PHP;
    }
}
