<?php

declare(strict_types=1);

namespace Beaver\Cache\Contracts;

interface CacheDriver
{
    /**
     * Guarda um valor com TTL (segundos). TTL null = para sempre.
     */
    public function put(string $key, mixed $value, ?int $ttl = null): void;

    /**
     * Devolve o valor ou null se não existir / expirou.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Existe e está válido?
     */
    public function has(string $key): bool;

    /**
     * Apaga uma chave.
     */
    public function forget(string $key): void;

    /**
     * Apaga tudo.
     */
    public function flush(): void;
}
