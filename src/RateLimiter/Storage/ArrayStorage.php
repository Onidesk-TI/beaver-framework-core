<?php

declare(strict_types=1);

namespace Beaver\RateLimiter\Storage;

use Beaver\RateLimiter\Contracts\Storage;

/**
 * Storage em memória (testes).
 */
class ArrayStorage implements Storage
{
    /** @var array<string, array{count: int, expiresAt: int}> */
    private array $data = [];

    public function hit(string $key, int $decaySeconds): int
    {
        $now = time();

        // Se expirou ou não existe, começar do zero
        if (!isset($this->data[$key]) || $this->data[$key]['expiresAt'] < $now) {
            $this->data[$key] = [
                'count'     => 1,
                'expiresAt' => $now + $decaySeconds,
            ];
            return 1;
        }

        $this->data[$key]['count']++;
        return $this->data[$key]['count'];
    }

    public function count(string $key): int
    {
        if (!isset($this->data[$key])) {
            return 0;
        }

        if ($this->data[$key]['expiresAt'] < time()) {
            unset($this->data[$key]);
            return 0;
        }

        return $this->data[$key]['count'];
    }

    public function clear(string $key): void
    {
        unset($this->data[$key]);
    }

    public function availableAt(string $key): ?int
    {
        if (!isset($this->data[$key])) {
            return null;
        }

        return $this->data[$key]['expiresAt'];
    }
}
