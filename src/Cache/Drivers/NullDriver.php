<?php

declare(strict_types=1);

namespace Beaver\Cache\Drivers;

use Beaver\Cache\Contracts\CacheDriver;

/**
 * Driver que não faz nada. Útil para desativar cache em produção.
 */
class NullDriver implements CacheDriver
{
    public function put(string $key, mixed $value, ?int $ttl = null): void
    {
        // no-op
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    public function has(string $key): bool
    {
        return false;
    }

    public function forget(string $key): void
    {
        // no-op
    }

    public function flush(): void
    {
        // no-op
    }
}
