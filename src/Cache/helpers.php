<?php

declare(strict_types=1);

/**
 * Beaver Framework — Cache helpers.
 */

use Beaver\Cache\Cache;
use Beaver\Foundation\Application;

if (!function_exists('cache')) {
    function cache(): Cache
    {
        return Application::getInstance()->make(Cache::class);
    }
}

if (!function_exists('cache_put')) {
    function cache_put(string $key, mixed $value, ?int $ttl = null): void
    {
        cache()->put($key, $value, $ttl);
    }
}

if (!function_exists('cache_get')) {
    function cache_get(string $key, mixed $default = null): mixed
    {
        return cache()->get($key, $default);
    }
}

if (!function_exists('cache_has')) {
    function cache_has(string $key): bool
    {
        return cache()->has($key);
    }
}

if (!function_exists('cache_forget')) {
    function cache_forget(string $key): void
    {
        cache()->forget($key);
    }
}

if (!function_exists('cache_flush')) {
    function cache_flush(): void
    {
        cache()->flush();
    }
}

if (!function_exists('cache_remember')) {
    function cache_remember(string $key, int $ttl, callable $callback): mixed
    {
        return cache()->remember($key, $ttl, $callback);
    }
}

if (!function_exists('cache_pull')) {
    function cache_pull(string $key, mixed $default = null): mixed
    {
        return cache()->pull($key, $default);
    }
}
