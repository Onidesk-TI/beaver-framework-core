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

// beaver-framework/src/Console/Application.php

namespace Beaver\Console;

class Application
{
    protected array $commands = [];

    public function __construct(
        protected string $frameworkPath,
        protected string $projectPath
    ) {
        $this->registerFrameworkCommands();
        $this->registerDefaultCommands();
        $this->registerProjectCommands();
        $this->registerPluginCommands();
    }

    /**
     * Comandos que vêm com o framework:
     *   <framework>/src/Console/Commands/*Command.php
     * Namespace: Beaver\Console\Commands
     */
    protected function registerFrameworkCommands(): void
    {
        $dir = $this->frameworkPath . '/src/Console/Commands';

        if (!is_dir($dir)) {
            return;
        }

        foreach (glob($dir . '/*Command.php') as $file) {
            $class = 'Beaver\\Console\\Commands\\' . basename($file, '.php');

            if (class_exists($class)) {
                $instance = new $class();
                $this->commands[$instance->name()] = $class;
            }
        }
    }

    /**
     * Comandos default hardcoded (App\Commands\Certify*).
     */
    protected function registerDefaultCommands(): void
    {
        $defaults = [
            \App\Commands\CertifyMakeCommand::class,
            \App\Commands\CertifyCheckCommand::class,
        ];

        foreach ($defaults as $class) {
            if (class_exists($class)) {
                $instance = new $class();
                $this->commands[$instance->name()] = $class;
            }
        }
    }

    /**
     * Comandos do projeto:
     *   <project>/app/Commands/*Command.php
     * Namespace: App\Commands
     */
    protected function registerProjectCommands(): void
    {
        $dir = $this->projectPath . '/app/Commands';

        if (!is_dir($dir)) {
            return;
        }

        foreach (glob($dir . '/*Command.php') as $file) {
            $class = 'App\\Commands\\' . basename($file, '.php');

            if (class_exists($class)) {
                $instance = new $class();
                $this->commands[$instance->name()] = $class;
            }
        }
    }

    /**
     * Comandos de plugins:
     *   <project>/plugins/<slug>/commands/*Command.php
     * Namespace declarado no plugin.json ("namespace" + "\\Commands\\")
     */
    protected function registerPluginCommands(): void
    {
        $pluginsDir = $this->projectPath . '/plugins';

        if (!is_dir($pluginsDir)) {
            return;
        }

        foreach (glob($pluginsDir . '/*', GLOB_ONLYDIR) as $pluginDir) {
            $commandsDir = $pluginDir . '/src/Commands';

            if (!is_dir($commandsDir)) {
                continue;
            }

            $manifestFile = $pluginDir . '/plugin.json';
            if (!is_file($manifestFile)) {
                continue;
            }

            $manifest = json_decode((string) file_get_contents($manifestFile), true) ?: [];
            $namespace = $manifest['namespace'] ?? null;

            if ($namespace === null) {
                continue;
            }

            $namespace = rtrim($namespace, '\\') . '\\Commands\\';

            foreach (glob($commandsDir . '/*Command.php') as $file) {
                $class = $namespace . basename($file, '.php');

                if (class_exists($class)) {
                    $instance = new $class();
                    $this->commands[$instance->name()] = $class;
                }
            }
        }
    }

    public function run(array $argv): int
    {
        $commandName = $argv[1] ?? null;

        if ($commandName === null || in_array($commandName, ['help', '--help', '-h'], true)) {
            $this->showHelp();
            return 0;
        }

        if (!isset($this->commands[$commandName])) {
            fwrite(STDERR, "\033[31m ✗ Comando desconhecido: {$commandName}\033[0m\n");
            fwrite(STDERR, "   Corre 'php beaver help' para ver os comandos disponíveis.\n");
            return 1;
        }

        $class = $this->commands[$commandName];
        $command = new $class();
        $command->setProjectPath($this->projectPath);
        $command->parse(array_slice($argv, 2));

        return $command->handle();
    }

    protected function showHelp(): void
    {
        echo "\n\033[38;5;93m🦫 Beaver Framework CLI\033[0m\n";
        echo "═══════════════════════════════════════════════════════════\n\n";
        echo "Uso: php beaver <comando> [opções]\n\n";
        echo "Comandos disponíveis:\n\n";

        ksort($this->commands);

        foreach ($this->commands as $name => $class) {
            $instance = new $class();
            printf("  \033[33m%-22s\033[0m %s\n", $name, $instance->description());
        }

        echo "\n";
    }
}
