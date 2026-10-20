<?php

declare(strict_types=1);

namespace Beaver\Cache\Drivers;

use Beaver\Cache\Contracts\CacheDriver;

/**
 * Driver em memória (não persiste). Útil para testes.
 */
class ArrayDriver implements CacheDriver
{
    /** @var array<string, array{value: mixed, expiresAt: int}> */
    private array $data = [];

    public function put(string $key, mixed $value, ?int $ttl = null): void
    {
        $this->data[$key] = [
            'value'     => $value,
            'expiresAt' => $ttl === null ? 0 : time() + $ttl,
        ];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!isset($this->data[$key])) {
            return $default;
        }

        $entry = $this->data[$key];

        if ($entry['expiresAt'] !== 0 && $entry['expiresAt'] < time()) {
            unset($this->data[$key]);
            return $default;
        }

        return $entry['value'];
    }

    public function has(string $key): bool
    {
        return $this->get($key, '__miss__') !== '__miss__';
    }

    public function forget(string $key): void
    {
        unset($this->data[$key]);
    }

    public function flush(): void
    {
        $this->data = [];
    }
}
