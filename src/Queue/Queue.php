<?php

declare(strict_types=1);

namespace Beaver\Queue;

use Beaver\Queue\Contracts\QueueDriver;

class Queue
{
    public function __construct(
        private QueueDriver $driver,
    ) {
    }

    /**
     * Enfileira um job para execução imediata.
     */
    public function push(Job $job): void
    {
        $this->driver->push($this->payloadFor($job), $job->queue());
    }

    /**
     * Enfileira um job para execução diferida.
     */
    public function later(int $delaySeconds, Job $job): void
    {
        $this->driver->later($delaySeconds, $this->payloadFor($job), $job->queue());
    }

    /**
     * Executa o próximo job da fila (para workers).
     *
     * @return bool true se executou algo, false se fila vazia
     */
    public function work(string $queue = 'default'): bool
    {
        $payload = $this->driver->pop($queue);

        if ($payload === null) {
            return false;
        }

        try {
            $job = $payload->resolve();
            $job->handle();

            $this->driver->delete($payload->id);
        } catch (\Throwable $e) {
            $this->handleFailure($payload, $e);
        }

        return true;
    }

    private function handleFailure(JobPayload $payload, \Throwable $e): void
    {
        $error = get_class($e) . ': ' . $e->getMessage();

        if ($payload->attempts >= $payload->maxAttempts) {
            $this->driver->fail($payload->id, $error);
            return;
        }

        // Re-tentar com backoff
        $backoff = $payload->resolve()->backoff();
        $this->driver->release($payload->id, $backoff);
    }

    public function size(string $queue = 'default'): int
    {
        return $this->driver->size($queue);
    }

    /** @return array<int, JobPayload> */
    public function list(string $queue = 'default', int $limit = 50): array
    {
        return $this->driver->list($queue, $limit);
    }

    public function clear(?string $queue = null): int
    {
        return $this->driver->clear($queue);
    }

    public function driver(): QueueDriver
    {
        return $this->driver;
    }

    private function payloadFor(Job $job): JobPayload
    {
        return new JobPayload(
            id:          null,
            class:       get_class($job),
            data:        $job->payloadData(),
            queue:       $job->queue(),
            maxAttempts: $job->maxAttempts(),
            attempts:    0,
            availableAt: time(),
            createdAt:   time(),
        );
    }
}
