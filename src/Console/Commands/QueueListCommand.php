<?php

declare(strict_types=1);

namespace Beaver\Console\Commands;

use Beaver\Console\Command;
use Beaver\Foundation\Application;
use Beaver\Queue\Queue;

class QueueListCommand extends Command
{
    protected string $signature = 'queue:list {--queue=default} {--limit=50}';
    protected string $description = 'Lista jobs pendentes na fila';

    public function handle(): int
    {
        $app = Application::getInstance();
        $app->boot();

        $queueName = (string) ($this->option('queue') ?: 'default');
        $limit     = (int) ($this->option('limit') ?: 50);

        $queue = $app->make(Queue::class);
        $jobs  = $queue->list($queueName, $limit);

        $this->line();
        $this->info("Fila: {$queueName}");
        $this->line();

        if ($jobs === []) {
            $this->warning('Sem jobs pendentes.');
            return self::SUCCESS;
        }

        printf("  %-8s %-20s %-8s %-10s %s\n",
            'ID', 'CLASS', 'ATTEMPTS', 'WAIT', 'CREATED');
        printf("  %s\n", str_repeat('─', 90));

        foreach ($jobs as $job) {
            $shortClass = (new \ReflectionClass($job->class))->getShortName();
            $wait       = max(0, $job->availableAt - time());

            printf("  %-8s %-20s %-8s %-10s %s\n",
                (string) $job->id,
                $shortClass,
                "{$job->attempts}/{$job->maxAttempts}",
                $wait > 0 ? "{$wait}s" : 'ready',
                date('Y-m-d H:i:s', $job->createdAt),
            );
        }

        $this->line();
        $this->info(count($jobs) . " job(s) listado(s).");

        return self::SUCCESS;
    }
}
