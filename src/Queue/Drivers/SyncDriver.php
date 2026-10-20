<?php

declare(strict_types=1);

namespace Beaver\Queue\Drivers;

use Beaver\Queue\Contracts\QueueDriver;
use Beaver\Queue\JobPayload;

/**
 * Executa o job imediatamente (sincronamente).
 * Útil em dev — não há workers.
 */
class SyncDriver implements QueueDriver
{
    public function push(JobPayload $payload, string $queue = 'default'): void
    {
        $this->run($payload);
    }

    public function later(int $delaySeconds, JobPayload $payload, string $queue = 'default'): void
    {
        // Sync não sabe adiar — corre logo
        $this->run($payload);
    }

    public function pop(string $queue = 'default'): ?JobPayload { return null; }
    public function delete(int|string $id): void {}
    public function fail(int|string $id, string $error): void {}
    public function release(int|string $id, int $delaySeconds): void {}
    public function size(string $queue = 'default'): int { return 0; }
    public function list(string $queue = 'default', int $limit = 50): array { return []; }
    public function clear(?string $queue = null): int { return 0; }

    private function run(JobPayload $payload): void
    {
        $job = $payload->resolve();
        $job->handle();
    }
}
