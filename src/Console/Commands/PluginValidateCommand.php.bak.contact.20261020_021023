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

class PluginValidateCommand extends Command
{
    protected string $signature = 'plugin:validate {slug?} {--json}';
    protected string $description = 'Valida o plugin.json (de todos ou de um plugin específico)';

    public function handle(): int
    {
        $target = (string) $this->argument('slug', '');

        $app    = Application::getInstance();
        $mode   = (string) $app->config('app.plugins.mode', 'prod');
        $config = (array)  $app->config('app.plugins', []);

        $found = PluginPaths::manifests($mode, $config);

        $results = [];
        $failed  = 0;

        foreach ($found as $entry) {
            if ($target !== '' && $entry['slug'] !== $target) {
                continue;
            }
            $errors = ManifestValidator::validate($entry['data']);

            $results[] = [
                'slug'   => $entry['slug'],
                'source' => $entry['source'],
                'errors' => $errors,
            ];
            if ($errors) {
                $failed++;
            }
        }

        if ($this->option('json')) {
            echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
            return $failed === 0 ? self::SUCCESS : self::FAILURE;
        }

        if (!$results) {
            $this->warning($target !== ''
                ? "Plugin não encontrado: $target"
                : "Nenhum plugin.json encontrado (mode=$mode).");
            return self::FAILURE;
        }

        foreach ($results as $r) {
            $label = sprintf('%s [%s]', $r['slug'], $r['source']);
            if ($r['errors']) {
                $this->error($label);
                foreach ($r['errors'] as $e) {
                    $this->line("     - $e");
                }
            } else {
                $this->success($label);
            }
        }

        $this->line();
        if ($failed === 0) {
            $this->success(count($results) . ' plugin(s) válido(s).');
            return self::SUCCESS;
        }

        $this->error("$failed de " . count($results) . " plugin(s) com erro.");
        return self::FAILURE;
    }
}
