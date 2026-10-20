<?php

declare(strict_types=1);

namespace Beaver\Console\Commands;

use Beaver\Console\Command;
use Beaver\Scheduling\ScheduledJob;

class JobListCommand extends Command
{
    protected string $signature   = 'job:list {--active}';
    protected string $description = 'Lista os jobs agendados';

    public function handle(): int
    {
        $jobs = $this->option('active')
            ? ScheduledJob::active()
            : ScheduledJob::all();

        if ($jobs === []) {
            $this->warning('Não há jobs registados.');
            return self::SUCCESS;
        }

        $this->line();
        $this->line(sprintf(
            '  %-30s %-28s %-7s %-16s %s',
            'NAME', 'HANDLER', 'ATIVO', 'CRON', 'ÚLTIMO STATUS'
        ));
        $this->line('  ' . str_repeat('─', 110));

        foreach ($jobs as $job) {
            $this->line(sprintf(
                '  %-30s %-28s %-7s %-16s %s',
                $this->truncate((string) $job->name, 30),
                $this->shortClass((string) $job->handler, 28),
                ($job->is_active ?? false) ? 'sim' : 'não',
                (string) $job->cron_expression,
                (string) ($job->last_status ?? '—'),
            ));
        }

        $this->line();
        return self::SUCCESS;
    }

    private function shortClass(string $fqcn, int $max): string
    {
        $pos = strrpos($fqcn, '\\');
        $short = $pos !== false ? substr($fqcn, $pos + 1) : $fqcn;
        return $this->truncate($short, $max);
    }

    private function truncate(string $s, int $max): string
    {
        return strlen($s) <= $max ? $s : substr($s, 0, $max - 1) . '…';
    }
}
