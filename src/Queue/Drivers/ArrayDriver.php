<?php

declare(strict_types=1);

namespace Beaver\Queue\Drivers;

use Beaver\Queue\Contracts\QueueDriver;
use Beaver\Queue\JobPayload;

/**
 * Driver em memória (testes).
 */
class ArrayDriver implements QueueDriver
{
    /** @var array<int, JobPayload> */
    private array $jobs = [];

    private int $nextId = 1;

    /** @var array<int, array{payload: JobPayload, error: string}> */
    private array $failed = [];

    public function push(JobPayload $payload, string $queue = 'default'): void
    {
        $this->jobs[] = $this->withId($payload);
    }

    public function later(int $delaySeconds, JobPayload $payload, string $queue = 'default'): void
    {
        $delayed = new JobPayload(
            id:          $this->nextId++,
            class:       $payload->class,
            data:        $payload->data,
            queue:       $payload->queue,
            maxAttempts: $payload->maxAttempts,
            attempts:    $payload->attempts,
            availableAt: time() + $delaySeconds, //delay
            createdAt:   $payload->createdAt,
        );

        $this->jobs[] = $delayed;
    }

    public function pop(string $queue = 'default'): ?JobPayload
    {
        $now = time();

        foreach ($this->jobs as $i => $job) {
            if ($job->queue === $queue && $job->availableAt <= $now) {
                unset($this->jobs[$i]);
                $this->jobs = array_values($this->jobs);
                return $job;
            }
        }

        return null;
    }

    public function delete(int|string $id): void
    {
        $this->jobs = array_values(array_filter(
            $this->jobs,
            fn ($j) => $j->id !== $id,
        ));
    }

    public function fail(int|string $id, string $error): void
    {
        foreach ($this->jobs as $job) {
            if ($job->id === $id) {
                $this->failed[] = ['payload' => $job, 'error' => $error];
                $this->delete($id);
                return;
            }
        }
    }

    public function release(int|string $id, int $delaySeconds): void
    {
        // Nada a fazer — o job fica onde está
    }

    public function size(string $queue = 'default'): int
    {
        return count($this->jobs);
    }

    public function list(string $queue = 'default', int $limit = 50): array
    {
        return array_slice($this->jobs, 0, $limit);
    }

    public function clear(?string $queue = null): int
    {
        $count = count($this->jobs);
        $this->jobs = [];
        return $count;
    }

    public function failed(): array
    {
        return $this->failed;
    }

    private function withId(JobPayload $payload): JobPayload
    {
        return new JobPayload(
            id:          $this->nextId++,
            class:       $payload->class,
            data:        $payload->data,
            queue:       $payload->queue,
            maxAttempts: $payload->maxAttempts,
            attempts:    $payload->attempts,
            availableAt: $payload->availableAt,
            createdAt:   $payload->createdAt,
        );
    }
}
