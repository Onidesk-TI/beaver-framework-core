<?php

declare(strict_types=1);

namespace Beaver\Console\Commands;

use Beaver\Console\Command;
use Beaver\Scheduling\CronManager;
use Beaver\Scheduling\ScheduledJob;
use Throwable;

class CronSyncCommand extends Command
{
    protected string $signature   = 'cron:sync {--dry-run}';
    protected string $description = 'Sincroniza os jobs ativos com o cron do sistema';

    public function handle(): int
    {
        $jobs = ScheduledJob::active();

        if ($jobs === []) {
            $this->warning('Não há jobs ativos para sincronizar.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry-run — não escreve no cron do SO.');
            $this->line();
            foreach ($jobs as $job) {
                $this->line(sprintf(
                    '  %-25s %-18s  →  %s',
                    $job->name,
                    $job->cron_expression,
                    $job->handler,
                ));
            }
            $this->line();
            $this->warning('Nada foi escrito. Corre sem --dry-run para aplicar.');
            return self::SUCCESS;
        }

        try {
            $manager = new CronManager();
            $total   = $manager->syncAll();

            $this->success("Sincronizados {$total} jobs no cron do SO.");
            $this->line("  Driver: " . $manager->driver()->driverName());

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Falhou: ' . $e->getMessage());
            $this->line('  Se for permissão em /etc/cron.d/, tenta com sudo.');
            return self::FAILURE;
        }
    }
}
