<?php

declare(strict_types=1);

namespace Beaver\Console\Commands;

use Beaver\Console\Command;
use Beaver\Scheduling\JobRunner;
use Beaver\Scheduling\ScheduledJob;
use Throwable;

class JobRunAllCommand extends Command
{
    protected string $signature   = 'job:run-all {--force} {--dry-run}';
    protected string $description = 'Executa todos os jobs ativos';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $jobs   = ScheduledJob::active();

        if ($jobs === []) {
            $this->warning('Não há jobs ativos.');
            return self::SUCCESS;
        }

        $this->info('A correr ' . count($jobs) . ' jobs...');
        $this->line();

        $ok    = 0;
        $fail  = 0;
        $start = microtime(true);

        foreach ($jobs as $job) {
            if ($dryRun) {
                $this->line("  [dry-run] {$job->name} -> {$job->handler}");
                continue;
            }

            $t = microtime(true);

            try {
                (new JobRunner())->run($job);
                $ms = round((microtime(true) - $t) * 1000);
                $this->success("  {$job->name} ({$ms}ms)");
                $ok++;
            } catch (Throwable $e) {
                $this->error("  {$job->name}: {$e->getMessage()}");
                $fail++;
            }
        }

        if ($dryRun) {
            $this->warning('Dry-run: nada foi executado.');
            return self::SUCCESS;
        }

        $total = round(microtime(true) - $start, 2);
        $this->line();
        $this->info("Concluído em {$total}s — {$ok} ok, {$fail} falhas");

        return $fail === 0 ? self::SUCCESS : self::FAILURE;
    }
}
