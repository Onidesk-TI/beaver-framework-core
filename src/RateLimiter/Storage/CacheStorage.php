<?php

declare(strict_types=1);

namespace Beaver\RateLimiter\Storage;

use Beaver\Cache\Cache;
use Beaver\RateLimiter\Contracts\Storage;

/**
 * Storage que usa o subsistema Cache do Beaver.
 */
class CacheStorage implements Storage
{
    public function __construct(
        private Cache $cache,
        private string $prefix = 'rate_limiter:',
    ) {
    }

    public function hit(string $key, int $decaySeconds): int
    {
        $full = $this->prefix . $key;

        // Chave de expiração separada (para availableAt)
        $expiresKey = $full . ':expires';
        $expiresAt  = $this->cache->get($expiresKey);

        // Se expirou ou não existe, reset
        if ($expiresAt === null || (int) $expiresAt < time()) {
            $this->cache->put($full, 1, $decaySeconds);
            $this->cache->put($expiresKey, time() + $decaySeconds, $decaySeconds);
            return 1;
        }

        // Incrementar mantendo o TTL original
        $count   = (int) $this->cache->get($full, 0) + 1;
        $ttlLeft = max(1, (int) $expiresAt - time());

        $this->cache->put($full, $count, $ttlLeft);

        return $count;
    }

    public function count(string $key): int
    {
        return (int) $this->cache->get($this->prefix . $key, 0);
    }

    public function clear(string $key): void
    {
        $full = $this->prefix . $key;
        $this->cache->forget($full);
        $this->cache->forget($full . ':expires');
    }

    public function availableAt(string $key): ?int
    {
        $expires = $this->cache->get($this->prefix . $key . ':expires');

        return $expires === null ? null : (int) $expires;
    }
}
