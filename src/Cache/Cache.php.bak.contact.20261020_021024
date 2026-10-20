<?php

/**
 * Beaver Framework — Modern PHP framework with a plugin ecosystem.
 *
 * @package    Beaver Framework
 * @author     Franco <onidesk@outlook.com>
 * @copyright  2026 Onidesk
 * @license    MIT <https://opensource.org/licenses/MIT>
 * @link       https://github.com/Onidesk-TI/beaver-framework
 *
 * ──────────────────────────────────────────────────────────────
 * API HÍBRIDA — três formas de usar
 * ──────────────────────────────────────────────────────────────
 *
 *   1) Helper global (mais curto, uso comum)
 *         cache_put('users', $data, 3600);
 *         cache_get('users');
 *         cache_remember('users', 3600, fn() => User::all());
 *
 *   2) Container (controlo, testes, injeção)
 *         $cache = app()->make(Cache::class);
 *         $cache->put('users', $data, 3600);
 *
 *   3) Injeção de dependências (controllers, services)
 *         public function __construct(private Cache $cache) {}
 */

namespace Beaver\Cache;

use Beaver\Cache\Contracts\CacheDriver;

class Cache
{
    public function __construct(
        private CacheDriver $driver,
    ) {
    }

    public function put(string $key, mixed $value, ?int $ttl = null): void
    {
        $this->driver->put($key, $value, $ttl);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->driver->get($key, $default);
    }

    public function has(string $key): bool
    {
        return $this->driver->has($key);
    }

    public function forget(string $key): void
    {
        $this->driver->forget($key);
    }

    public function flush(): void
    {
        $this->driver->flush();
    }

    /**
     * O padrão mágico: devolve do cache, ou calcula e guarda.
     *
     *     $users = cache_remember('users', 3600, fn() => User::all());
     *
     * 1ª vez  → vai ao callback, guarda no cache, devolve
     * Durante TTL → devolve do cache (sem chamar o callback)
     * Após TTL → volta a chamar o callback
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $value = $callback();

        $this->put($key, $value, $ttl);

        return $value;
    }

    /**
     * Devolve o valor e apaga-o.
     */
    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->forget($key);
        return $value;
    }

    /**
     * Incrementa um valor numérico (atómico só em drivers que suportem).
     */
    public function increment(string $key, int $by = 1): int
    {
        $value = (int) $this->get($key, 0) + $by;
        $this->put($key, $value, null);
        return $value;
    }

    /**
     * Decrementa um valor numérico.
     */
    public function decrement(string $key, int $by = 1): int
    {
        return $this->increment($key, -$by);
    }

    /**
     * Devolve o driver atual.
     */
    public function driver(): CacheDriver
    {
        return $this->driver;
    }
}
