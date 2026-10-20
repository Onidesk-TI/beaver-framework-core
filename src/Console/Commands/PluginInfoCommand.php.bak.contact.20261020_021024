<?php

declare(strict_types=1);

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
use Beaver\Foundation\Application;
use Beaver\Sdk\Manifest;
use Beaver\Sdk\PluginPaths;

class PluginInfoCommand extends Command
{
    protected string $signature = 'plugin:info {slug} {--json}';
    protected string $description = 'Mostra detalhes de um plugin (manifesto + ficheiro principal)';

    public function handle(): int
    {
        $slug = (string) $this->argument('slug', '');
        if ($slug === '') {
            $this->error('Falta o slug do plugin.');
            $this->line('Uso: php beaver plugin:info <slug>');
            return self::INVALID;
        }

        $app    = Application::getInstance();
        $mode   = (string) $app->config('app.plugins.mode', 'prod');
        $config = (array)  $app->config('app.plugins', []);

        $entry = null;
        foreach (PluginPaths::manifests($mode, $config) as $candidate) {
            if ($candidate['slug'] === $slug) {
                $entry = $candidate;
                break;
            }
        }

        if ($entry === null) {
            $this->error("Plugin não encontrado: $slug (mode=$mode)");
            return self::FAILURE;
        }

        $m = Manifest::fromArray($entry['data']);

        if ($this->option('json')) {
            echo json_encode($m->raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
            return self::SUCCESS;
        }

        $this->line();
        $this->line("  \033[38;5;93m{$m->name}\033[0m  [{$entry['source']}]");
        $this->line('  ' . str_repeat('─', 55));
        $this->line();
        $this->line("  Slug        : {$m->slug}");
        $this->line("  Version     : {$m->version}");
        $this->line('  Author      : ' . ($m->author !== '' ? $m->author : '—'));
        $this->line("  Namespace   : {$m->namespace}");
        $this->line("  Main        : {$m->main}");
        $this->line("  API version : {$m->apiVersion}");
        $this->line("  Framework   : {$m->beaverVersion}");
        $this->line('  Description : ' . ($m->description !== '' ? $m->description : '—'));
        $this->line("  Source      : {$entry['source']}");
        $this->line("  Path        : {$entry['path']}");

        if ($m->permissions) {
            $this->line();
            $this->line('  Permissions (raw):');
            $this->line('    ' . json_encode($m->permissions, JSON_UNESCAPED_SLASHES));
        }

        $this->line();

        $mainFile = $entry['path'] . '/' . $m->main;
        if (is_file($mainFile)) {
            $this->success('Main file existe: ' . $m->main);
        } else {
            $this->error('Main file NÃO existe: ' . $m->main);
        }

        $this->line();
        return self::SUCCESS;
    }
}
