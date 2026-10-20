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
use Beaver\Sdk\ManifestValidator;
use Beaver\Sdk\PluginPaths;

class PluginListCommand extends Command
{
    protected string $signature = 'plugin:list {--json}';
    protected string $description = 'Lista todos os plugins descobertos (todos os paths do mode ativo)';

    public function handle(): int
    {
        $app    = Application::getInstance();
        $mode   = (string) $app->config('app.plugins.mode', 'prod');
        $config = (array)  $app->config('app.plugins', []);

        $rows = PluginPaths::manifests($mode, $config);

        foreach ($rows as &$r) {
            $errors = ManifestValidator::validate($r['data']);
            $r['valid']   = empty($errors);
            $r['name']    = $r['data']['name']    ?? '—';
            $r['version'] = $r['data']['version'] ?? '—';
        }
        unset($r);

        if ($this->option('json')) {
            $clean = array_map(static fn ($r) => [
                'slug'    => $r['slug'],
                'name'    => $r['name'],
                'version' => $r['version'],
                'source'  => $r['source'],
                'path'    => $r['path'],
                'valid'   => $r['valid'],
            ], $rows);

            echo json_encode($clean, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
            return self::SUCCESS;
        }

        if (!$rows) {
            $this->warning("Nenhum plugin encontrado (mode=$mode).");
            return self::SUCCESS;
        }

        $this->line();
        printf("  %-20s %-22s %-8s %-10s %s\n",
            'SLUG', 'NAME', 'VERSION', 'SOURCE', 'STATUS');
        printf("  %s\n", str_repeat('─', 80));

        foreach ($rows as $r) {
            printf("  %-20s %-22s %-8s %-10s %s\n",
                $r['slug'],
                $this->truncate($r['name'], 22),
                $r['version'],
                $r['source'],
                $r['valid'] ? "\033[32m✓\033[0m" : "\033[31m✗\033[0m"
            );
        }

        $this->line();
        $this->info(count($rows) . " plugin(s) encontrado(s) (mode=$mode).");

        return self::SUCCESS;
    }

    private function truncate(string $s, int $w): string
    {
        return mb_strlen($s) <= $w ? $s : mb_substr($s, 0, $w - 1) . '…';
    }
}
