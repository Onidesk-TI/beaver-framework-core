<?php

declare(strict_types=1);

namespace Beaver\Console\Commands;

use Beaver\Console\Command;
use Beaver\Foundation\Application;
use Beaver\Queue\Queue;

class QueueClearCommand extends Command
{
    protected string $signature = 'queue:clear {--queue=}';
    protected string $description = 'Apaga jobs da fila';

    public function handle(): int
    {
        $app = Application::getInstance();
        $app->boot();

        $queueName = $this->option('queue');

        if (!$this->option('queue')) {
            $this->warning('Isto apaga TODOS os jobs de TODAS as filas.');
            if (!$this->confirm('Tens a certeza?', false)) {
                $this->info('Cancelado.');
                return self::SUCCESS;
            }
        }

        $queue = $app->make(Queue::class);
        $count = $queue->clear($queueName ?: null);

        $this->success("{$count} job(s) apagado(s).");

        return self::SUCCESS;
    }
}
