<?php

declare(strict_types=1);

namespace Beaver\Console\Commands;

use Beaver\Console\Command;
use Beaver\Foundation\Application;
use Beaver\Queue\Queue;

class QueueWorkCommand extends Command
{
    protected string $signature = 'queue:work {--queue=default} {--once} {--sleep=3} {--max=0}';
    protected string $description = 'Processa jobs da fila';

    public function handle(): int
    {
        $app = Application::getInstance();
        $app->boot();

        $queueName = (string) ($this->option('queue') ?: 'default');
        $once      = (bool) $this->option('once');
        $sleep     = (int) ($this->option('sleep') ?: 3);
        $max       = (int) ($this->option('max') ?: 0);

        $queue = $app->make(Queue::class);

        $this->line();
        $this->info("🦫 Beaver Queue Worker");
        $this->line("  Fila:   {$queueName}");
        $this->line("  Modo:   " . ($once ? 'once' : 'loop'));
        $this->line("  Sleep:  {$sleep}s");
        $this->line("  Ctrl+C para parar");
        $this->line();

        $processed = 0;

        while (true) {
            try {
                $ran = $queue->work($queueName);

                if ($ran) {
                    $processed++;
                    $this->line("  ✓ Job processado (total: {$processed})");
                } else {
                    if ($once) {
                        break;
                    }
                    sleep($sleep);
                }

                if ($once) {
                    break;
                }

                if ($max > 0 && $processed >= $max) {
                    $this->info("Limite atingido ({$max}). A sair.");
                    break;
                }
            } catch (\Throwable $e) {
                $this->error('Erro no worker: ' . $e->getMessage());
                sleep($sleep);
            }
        }

        $this->line();
        $this->success("Worker terminou. Total processado: {$processed}");

        return self::SUCCESS;
    }
}
