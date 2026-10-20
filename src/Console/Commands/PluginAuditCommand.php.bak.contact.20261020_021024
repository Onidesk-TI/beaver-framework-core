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
use Beaver\Plugin\PluginManager;

class PluginAuditCommand extends Command
{
    protected string $signature = 'plugin:audit {--json} {--clear}';
    protected string $description = 'Mostra o registo de permissões (modo audit)';

    public function handle(): int
    {
        $app = Application::getInstance();
        $pm  = $app->make(PluginManager::class);

        $auditor = $pm->auditor();

        if ($this->option('clear')) {
            $log = $auditor->logFile();
            if ($log && is_file($log)) {
                @unlink($log);
                $this->success('Log apagado: ' . $log);
            } else {
                $this->warning('Sem log para apagar.');
            }
            return self::SUCCESS;
        }

        // Corre o boot para que os plugins tenham oportunidade de falar
        $pm->discover();
        $pm->boot();

        $events = $auditor->all();
        $mode   = (string) $app->config('app.plugins.permissions.mode', 'audit');

        if ($this->option('json')) {
            echo json_encode([
                'mode'   => $mode,
                'log'    => $auditor->logFile(),
                'events' => $events,
                'summary' => $auditor->summary(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
            return self::SUCCESS;
        }

        $this->line();
        $this->line("  \033[38;5;93mPlugin Permission Audit\033[0m");
        $this->line('  ' . str_repeat('─', 60));
        $this->line();
        $this->line("  Mode:  $mode");
        $this->line('  Log:   ' . ($auditor->logFile() ?? '(nenhum)'));
        $this->line();

        if (!$events) {
            $this->info('Nenhum evento registado nesta execução.');
            $this->line();
            $this->line('  Isto é normal se os plugins não tentaram usar hooks');
            $this->line('  ou outros recursos com verificação de permissão.');
            $this->line();
            return self::SUCCESS;
        }

        // Eventos por plugin
        $summary = $auditor->summary();
        $this->line('  \033[33mResumo por plugin:\033[0m');
        foreach ($summary as $slug => $scopes) {
            $this->line("    • $slug");
            foreach ($scopes as $scope => $count) {
                $this->line("        $scope  ×$count");
            }
        }

        // Últimos 20 eventos
        $this->line();
        $this->line('  \033[33mÚltimos eventos:\033[0m');
        $recent = array_slice(array_reverse($events), 0, 20);
        foreach ($recent as $e) {
            $ts = date('H:i:s', (int) $e['at']);
            $result = strtoupper($e['result']);
            $detail = $e['detail'] !== '' ? " ({$e['detail']})" : '';
            $this->line("    $ts  {$e['slug']}  {$e['scope']}  [$result]$detail");
        }

        $this->line();
        $this->info(count($events) . ' evento(s) no total.');
        $this->line();

        return self::SUCCESS;
    }
}
