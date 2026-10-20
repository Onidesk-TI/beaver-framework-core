<?php

declare(strict_types=1);

namespace Beaver\Console\Commands;

use Beaver\Console\Command;
use Beaver\Sdk\ManifestValidator;

/**
 * Beaver Framework — Modern PHP framework with a plugin ecosystem.
 *
 * @package    Beaver Framework
 * @author     Franco <onidesk@outlook.com>
 * @copyright  2026 Onidesk
 * @license    MIT <https://opensource.org/licenses/MIT>
 * @link       https://github.com/Onidesk-TI/beaver-framework
 */
class PluginMakeCommand extends Command
{
    protected string $signature = 'plugin:make {name} {--author=} {--dest=} {--force} {--no-register}';
    protected string $description = 'Cria um novo plugin compatível com o Beaver SDK';

    public function handle(): int
    {
        $rawName = (string) $this->argument('name', '');
        if ($rawName === '') {
            $this->error('Falta o nome do plugin.');
            $this->line('Uso: php beaver plugin:make <nome> [--author="Nome"]');
            return self::INVALID;
        }

        $slug   = $this->slugify($rawName);
        $studly = $this->studlify($slug);
        $label  = $this->humanize($slug);

        if ($slug === '' || $studly === '') {
            $this->error('Nome inválido após normalização.');
            return self::INVALID;
        }

        $author     = (string) ($this->option('author') ?: 'Onidesk');

        // --dest permite criar em qualquer pasta (ex: C:\meus-plugins ou /opt/plugins)
        $dest = (string) ($this->option('dest') ?: '');
        if ($dest !== '') {
            $dest = str_replace('\\\\', '/', $dest);
            if (!preg_match('#^([A-Za-z]:/|/)#', $dest)) {
                $dest = $this->projectPath . '/' . $dest;
            }
            $pluginsDir = rtrim($dest, '/');
        } else {
            $pluginsDir = $this->projectPath . '/plugins';
        }
        $pluginPath = $pluginsDir . '/' . $slug;

        if (!is_dir($pluginsDir)) {
            @mkdir($pluginsDir, 0775, true);
        }

        if (is_dir($pluginPath)) {
            if (!$this->option('force')) {
                $this->error("Já existe: $pluginPath");
                $this->line('Usa --force para substituir.');
                return self::FAILURE;
            }
            $this->warning('A remover existente: ' . $slug);
            $this->deleteDirectory($pluginPath);
        }

        $dirs = [
        '', 'src', 'src/Controllers', 'src/Models', 'src/Services',
        'config', 'lang', 'resources', 'resources/views', 'routes',
        ];
        foreach ($dirs as $d) {
            $path = $d === '' ? $pluginPath : $pluginPath . '/' . $d;
            if (!@mkdir($path, 0775, true) && !is_dir($path)) {
                $this->error("Não consegui criar: $path");
                return self::FAILURE;
            }
        }

        $namespace = "Beaver\\Plugins\\$studly";
        $className = "{$studly}Plugin";
        $mainFile  = "src/{$className}.php";

        $manifest = [
        'name'           => $label,
        'slug'           => $slug,
        'version'        => '0.1.0',
        'author'         => $author,
        'description'    => "Plugin $label para Beaver Framework",
        'namespace'      => $namespace,
        'main'           => $mainFile,
        'beaver_version' => '>=0.1.0',
        'api_version'    => '1.0.0',
        'permissions'    => [],
        'admin_menu'     => ['label' => $label, 'icon' => 'fa-cube'],
        'routes'         => 'routes/web.php',
        ];

        $this->write(
            $pluginPath . '/plugin.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
        );

        $this->write($pluginPath . '/' . $mainFile, $this->tplPluginClass($studly));
        $this->write($pluginPath . '/routes/web.php', $this->tplRoutes($studly, $slug));
        $this->write($pluginPath . '/lang/pt.php', $this->tplLang($label));
        $this->write($pluginPath . '/lang/en.php', $this->tplLang($label));
        $this->write($pluginPath . '/config/settings.php', $this->tplConfig());
        $this->write($pluginPath . '/README.md', $this->tplReadme($label, $slug, $studly));
        $this->write($pluginPath . '/.gitignore', $this->tplGitignore());

        @touch($pluginPath . '/resources/views/.gitkeep');
        @touch($pluginPath . '/src/Controllers/.gitkeep');
        @touch($pluginPath . '/src/Models/.gitkeep');
        @touch($pluginPath . '/src/Services/.gitkeep');

        @chmod($pluginPath, 0775);
        $this->chmodRecursive($pluginPath);

        $errors = ManifestValidator::validate($manifest);

        $this->line();
        $this->info("Plugin criado: $pluginPath");
        $this->line();

        if ($errors) {
            $this->warning('Manifesto gerado tem avisos:');
            foreach ($errors as $e) {
                $this->line("    - $e");
            }
        } else {
            $this->success('Manifesto válido pelo Beaver SDK.');
        }

        if (!$this->option('no-register')) {
            $normalProject = str_replace('\\\\', '/', $this->projectPath);
            $normalPlugin  = str_replace('\\\\', '/', $pluginPath);
            $rel = str_starts_with($normalPlugin, $normalProject . '/')
                ? substr($normalPlugin, strlen($normalProject) + 1)
                : $normalPlugin;
            $this->registerAutoload($namespace, $rel . '/src/');
        }

        $this->line();
        $this->line('Próximos passos:');
        $this->line('  1. composer dump-autoload');
        $this->line("  2. php beaver plugin:validate $slug");
        $this->line("  3. php beaver plugin:info $slug");

        return self::SUCCESS;
    }

    private function write(string $path, string $content): void
    {
        if (@file_put_contents($path, $content) === false) {
            throw new \RuntimeException("Falha ao escrever: $path");
        }
        @chmod($path, 0664);
    }

    private function chmodRecursive(string $dir): void
    {
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($it as $item) {
            @chmod($item->getPathname(), $item->isDir() ? 0775 : 0664);
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($it as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($dir);
    }

    private function slugify(string $raw): string
    {
        $s = preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $raw) ?? $raw;
        $s = preg_replace('/[\s_]+/', '-', $s) ?? $s;
        $s = strtolower($s);
        $s = preg_replace('/[^a-z0-9-]+/', '', $s) ?? $s;
        $s = preg_replace('/-+/', '-', $s) ?? $s;
        return trim($s, '-');
    }

    private function studlify(string $slug): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $slug)));
    }

    private function humanize(string $slug): string
    {
        return ucwords(str_replace('-', ' ', $slug));
    }

    private function registerAutoload(string $namespace, string $path): void
    {
        $file = $this->projectPath . '/composer.json';
        if (!is_file($file)) {
            $this->warning('composer.json não encontrado — regista o autoload à mão.');
            return;
        }

        $json = json_decode((string) file_get_contents($file), true);
        if (!is_array($json)) {
            $this->warning('composer.json inválido — regista o autoload à mão.');
            return;
        }

        $json['autoload']['psr-4'][$namespace . '\\'] = $path;

        $written = @file_put_contents(
            $file,
            json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
        );

        if ($written === false) {
            $this->warning('Não consegui escrever composer.json — regista o autoload à mão.');
            return;
        }

        $this->success("Autoload registado: $namespace\\ → $path");
    }

    private function tplPluginClass(string $studly): string
    {
        return <<<TPL
<?php

declare(strict_types=1);

namespace Beaver\\Plugins\\{$studly};

use Beaver\\Plugin\\PluginBase;

class {$studly}Plugin extends PluginBase
{
    public function boot(): void
    {
        \$this->loadViews();
        \$this->loadTranslations();
        \$this->loadRoutes();
    }
}
TPL;
    }

    private function tplRoutes(string $studly, string $slug): string
    {
        return <<<TPL
<?php
/**
 * Rotas do plugin {$studly}.
 *
 * @var \\Beaver\\Http\\Router \$router
 * @var \\Beaver\\Plugins\\{$studly}\\{$studly}Plugin \$plugin
 */

use Beaver\\Http\\Response;

\$router->get('/{$slug}', function () use (\$plugin) {
    return Response::json([
        'plugin'  => \$plugin->name(),
        'slug'    => \$plugin->slug(),
        'version' => \$plugin->version(),
        'source'  => 'plugins/{$slug}',
    ]);
});
TPL;
    }

    private function tplLang(string $label): string
    {
        return <<<TPL
<?php

return [
    'title' => '{$label}',
];
TPL;
    }

    private function tplConfig(): string
    {
        return <<<TPL
<?php

return [
    'enabled' => true,
    // Adiciona aqui as configurações do plugin.
];
TPL;
    }

    private function tplReadme(string $label, string $slug, string $studly): string
    {
        return <<<TPL
# {$label}

Plugin do Beaver Framework.

- **Slug:** `{$slug}`
- **Namespace:** `Beaver\\Plugins\\{$studly}`
- **Versão:** 0.1.0

## Estrutura

- `src/{$studly}Plugin.php` — classe principal (extends `PluginBase`)
- `routes/web.php` — rotas HTTP
- `resources/views/` — templates do plugin
- `lang/` — traduções
- `config/settings.php` — configuração

## Validar

    php beaver plugin:validate {$slug}
    php beaver plugin:info {$slug}
TPL;
    }

    private function tplGitignore(): string
    {
        return <<<TPL
/vendor/
composer.lock
.phpunit.result.cache
.idea/
.vscode/
*.log
.DS_Store
TPL;
    }
}
