<?php

declare(strict_types=1);

namespace Beaver\RateLimiter;

use Beaver\Cache\Cache;
use Beaver\RateLimiter\Contracts\Storage;
use Beaver\RateLimiter\Storage\ArrayStorage;
use Beaver\RateLimiter\Storage\CacheStorage;

class RateLimiterFactory
{
    public static function fromConfig(array $config, ?Cache $cache = null): Storage
    {
        $driver = $config['driver'] ?? 'cache';

        return match ($driver) {
            'cache' => new CacheStorage(
                $cache ?? throw new \RuntimeException('Cache required for cache storage'),
                $config['prefix'] ?? 'rate_limiter:',
            ),
            'array' => new ArrayStorage(),
            default => throw new \InvalidArgumentException("Unknown rate limiter storage: {$driver}"),
        };
    }
}
