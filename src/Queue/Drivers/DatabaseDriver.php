<?php

declare(strict_types=1);

namespace Beaver\Queue\Drivers;

use Beaver\Database\Database;
use Beaver\Queue\Contracts\QueueDriver;
use Beaver\Queue\JobPayload;

/**
 * Driver que guarda jobs numa tabela `jobs` na BD.
 */
class DatabaseDriver implements QueueDriver
{
    public function __construct(
        private Database $db,
        private string $table = 'jobs',
    ) {
    }

    public function push(JobPayload $payload, string $queue = 'default'): void
    {
        $this->insert($payload, $queue, 0);
    }

    public function later(int $delaySeconds, JobPayload $payload, string $queue = 'default'): void
    {
        $this->insert($payload, $queue, $delaySeconds);
    }

    public function pop(string $queue = 'default'): ?JobPayload
    {
        $now = time();

        // Reservar atomicamente (SQLite não tem SKIP LOCKED, mas é suficiente)
        $rows = $this->db->select(
            "SELECT id, queue, payload, available_at, attempts, max_attempts, created_at
             FROM {$this->table}
             WHERE queue = :queue
               AND available_at <= :now
               AND reserved_at IS NULL
               AND failed_at IS NULL
             ORDER BY available_at ASC, id ASC
             LIMIT 1",
            [':queue' => $queue, ':now' => $now],
        );

        if ($rows === []) {
            return null;
        }

        $row = $rows[0];

        $this->db->update(
            "UPDATE {$this->table}
             SET reserved_at = :now, attempts = attempts + 1
             WHERE id = :id",
            [':now' => $now, ':id' => $row['id']],
        );

        $row['reserved_at'] = $now;
        $row['attempts']    = (int) $row['attempts'] + 1;

        return $this->rowToPayload($row);
    }

    public function delete(int|string $id): void
    {
        $this->db->delete(
            "DELETE FROM {$this->table} WHERE id = :id",
            [':id' => $id],
        );
    }

    public function fail(int|string $id, string $error): void
    {
        $this->db->update(
            "UPDATE {$this->table}
             SET failed_at = :now, last_error = :error, reserved_at = NULL
             WHERE id = :id",
            [':now' => time(), ':error' => $error, ':id' => $id],
        );
    }

    public function release(int|string $id, int $delaySeconds): void
    {
        $this->db->update(
            "UPDATE {$this->table}
             SET reserved_at = NULL, available_at = :available
             WHERE id = :id",
            [':available' => time() + $delaySeconds, ':id' => $id],
        );
    }

    public function size(string $queue = 'default'): int
    {
        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS c FROM {$this->table}
             WHERE queue = :queue AND failed_at IS NULL",
            [':queue' => $queue],
        );

        return (int) ($row['c'] ?? 0);
    }

    public function list(string $queue = 'default', int $limit = 50): array
    {
        $rows = $this->db->select(
            "SELECT id, queue, payload, available_at, attempts, max_attempts, created_at
             FROM {$this->table}
             WHERE queue = :queue
             ORDER BY id ASC LIMIT :limit",
            [':queue' => $queue, ':limit' => $limit],
        );

        return array_map([$this, 'rowToPayload'], $rows);
    }

    public function clear(?string $queue = null): int
    {
        if ($queue === null) {
            $row = $this->db->selectOne("SELECT COUNT(*) AS c FROM {$this->table}");
            $this->db->query("DELETE FROM {$this->table}");
            return (int) ($row['c'] ?? 0);
        }

        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS c FROM {$this->table} WHERE queue = :queue",
            [':queue' => $queue],
        );
        $this->db->delete("DELETE FROM {$this->table} WHERE queue = :queue", [':queue' => $queue]);

        return (int) ($row['c'] ?? 0);
    }

    private function insert(JobPayload $payload, string $queue, int $delaySeconds): void
    {
        $now = time();

        $this->db->insert(
            "INSERT INTO {$this->table}
             (queue, payload, attempts, max_attempts, available_at, created_at)
             VALUES (:queue, :payload, 0, :max, :available, :created)",
            [
                ':queue'     => $queue,
                ':payload'   => json_encode([
                    'class' => $payload->class,
                    'data'  => $payload->data,
                ]),
                ':max'       => $payload->maxAttempts,
                ':available' => $now + $delaySeconds,
                ':created'   => $now,
            ],
        );
    }

    private function rowToPayload(array $row): JobPayload
    {
        $decoded = json_decode((string) $row['payload'], true) ?: [];

        return new JobPayload(
            id:          (int) $row['id'],
            class:       $decoded['class'] ?? '',
            data:        $decoded['data']  ?? [],
            queue:       (string) $row['queue'],
            maxAttempts: (int) $row['max_attempts'],
            attempts:    (int) $row['attempts'],
            availableAt: (int) $row['available_at'],
            createdAt:   (int) $row['created_at'],
        );
    }
}
