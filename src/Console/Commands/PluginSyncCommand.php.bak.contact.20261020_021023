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

class PluginSyncCommand extends Command
{
    protected string $signature = 'plugin:sync {slug?} {--dry-run} {--no-migrate} {--no-autoload} {--json}';
    protected string $description = 'Sincroniza plugins: valida manifestos, regista migrações, autoload e corre syncs específicos';

    public function handle(): int
    {
        $app    = Application::getInstance();
        $mode   = (string) $app->config('app.plugins.mode', 'prod');
        $config = (array)  $app->config('app.plugins', []);

        $rows = PluginPaths::manifests($mode, $config);

        if (!$rows) {
            $this->warning("Nenhum plugin encontrado (mode=$mode).");
            return self::SUCCESS;
        }

        // Filtro por slug
        $slug = $this->argument('slug');
        if ($slug) {
            $rows = array_values(array_filter($rows, fn ($r) => $r['slug'] === $slug));
            if (!$rows) {
                $this->error("Plugin '{$slug}' não encontrado.");
                return self::FAILURE;
            }
        }

        $dryRun     = (bool) $this->option('dry-run');
        $noMigrate  = (bool) $this->option('no-migrate');
        $noAutoload = (bool) $this->option('no-autoload');
        $jsonOut    = (bool) $this->option('json');

        $resultados = [];

        if (!$jsonOut) {
            $this->line();
            $this->info('🦫  Beaver — Plugin Sync' . ($dryRun ? ' (dry-run)' : ''));
            $this->line();
        }

        foreach ($rows as $r) {
            $slug = $r['slug'];
            $path = $r['path'];
            $info = [
                'slug'    => $slug,
                'path'    => $path,
                'valido'  => false,
                'erros'   => [],
                'acoes'   => [],
            ];

            // 1) Validar manifest
            $erros = ManifestValidator::validate($r['data']);
            $info['valido'] = empty($erros);
            if (!$info['valido']) {
                $info['erros'] = $erros;
                if (!$jsonOut) {
                    $this->error("{$slug}: manifest inválido");
                    foreach ($erros as $e) {
                        $this->line("    • {$e}");
                    }
                }
                $resultados[] = $info;
                continue;
            }

            if (!$jsonOut) {
                $this->line("  ▸ {$slug}");
            }

            // 2) Migrations — registar no MigrateCommand
            if (!$noMigrate) {
                $migDir = $path . '/database/migrations';
                if (is_dir($migDir)) {
                    if (!$dryRun && method_exists(\Beaver\Console\Commands\MigrateCommand::class, 'addPluginPath')) {
                        \Beaver\Console\Commands\MigrateCommand::addPluginPath($slug, $migDir);
                        $info['acoes'][] = 'migrations registadas';
                        if (!$jsonOut) $this->line("    ✓ migrations registadas ({$migDir})");
                    } else {
                        $info['acoes'][] = 'migrations (dry-run)';
                        if (!$jsonOut) $this->line("    • migrations: {$migDir}");
                    }
                }
            }

            // 3) Autoload — composer dump-autoload do plugin
            if (!$noAutoload && is_file($path . '/composer.json')) {
                if ($dryRun) {
                    $info['acoes'][] = 'autoload (dry-run)';
                    if (!$jsonOut) $this->line("    • autoload: composer dump-autoload em {$path}");
                } else {
                    $cmd = 'composer dump-autoload -d ' . escapeshellarg($path) . ' 2>&1';
                    exec($cmd, $out, $code);
                    if ($code === 0) {
                        $info['acoes'][] = 'autoload';
                        if (!$jsonOut) $this->line("    ✓ autoload");
                    } else {
                        $info['erros'][] = 'autoload falhou: ' . implode(' ', $out);
                        if (!$jsonOut) $this->error("    ✗ autoload falhou");
                    }
                }
            }

            // 4) Sync específico — procura comando no formato "<slug>:sync"
            //    convenção: comandos no namespace do plugin ou registados no binário
            $syncCmd = $this->descobrirSyncEspecifico($slug);
            if ($syncCmd) {
                $info['acoes'][] = "sync: {$syncCmd}";
                if ($dryRun) {
                    if (!$jsonOut) $this->line("    • sync específico: {$syncCmd}");
                } else {
                    $bin = $this->projectPath . '/beaver';
                    $cmd = 'php ' . escapeshellarg($bin) . ' ' . escapeshellarg($syncCmd) . ' 2>&1';
                    exec($cmd, $out2, $code2);
                    if ($code2 === 0) {
                        if (!$jsonOut) $this->line("    ✓ {$syncCmd}");
                    } else {
                        $info['erros'][] = "{$syncCmd} falhou";
                        if (!$jsonOut) $this->error("    ✗ {$syncCmd} falhou");
                    }
                }
            }

            $resultados[] = $info;
            if (!$jsonOut) $this->line();
        }

        // Output JSON
        if ($jsonOut) {
            echo json_encode($resultados, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
            return self::SUCCESS;
        }

        // Resumo
        $ok      = count(array_filter($resultados, fn ($r) => $r['valido'] && empty($r['erros'])));
        $total   = count($resultados);
        $comErro = $total - $ok;

        if ($comErro === 0) {
            $this->success("Sincronizados: {$ok}/{$total} plugin(s)");
            return self::SUCCESS;
        }

        $this->warning("Sincronizados: {$ok}/{$total} · com erros: {$comErro}");
        return self::FAILURE;
    }

    /**
     * Convenção: procura comando "<slug>:sync" na lista registada.
     * Ex.: plugin "beaver-cursos" → comando "cursos:sync"
     *      plugin "blog" → comando "blog:sync"
     */
    private function descobrirSyncEspecifico(string $slug): ?string
    {
        // Remove prefixo "beaver-", se existir
        $nome = preg_replace('/^beaver-/', '', $slug);

        $candidatos = [
            "{$slug}:sync",     // beaver-cursos:sync (raro)
            "{$nome}:sync",     // cursos:sync
        ];

        // Verifica se o comando existe na lista do Application
        $app = Application::getInstance();
        if (!method_exists($app, 'commands')) {
            // Fallback: procura classe CursosSyncCommand em Commands/
            foreach ($candidatos as $cmd) {
                $nomeClasse = str_replace([':', '-', '_'], '', ucwords($cmd, ':-_')) . 'Command';
                $classe = 'Beaver\\Console\\Commands\\' . $nomeClasse;
                if (class_exists($classe)) {
                    return $cmd;
                }
                $classeApp = 'App\\Commands\\' . $nomeClasse;
                if (class_exists($classeApp)) {
                    return $cmd;
                }
            }
            return null;
        }

        return null;
    }
}
