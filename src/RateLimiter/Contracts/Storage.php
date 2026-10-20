<?php

declare(strict_types=1);

namespace Beaver\RateLimiter\Contracts;

interface Storage
{
    /**
     * Incrementa o contador de uma chave.
     *
     * @return int Novo valor do contador
     */
    public function hit(string $key, int $decaySeconds): int;

    /**
     * Devolve o contador atual (0 se não existir).
     */
    public function count(string $key): int;

    /**
     * Apaga a chave.
     */
    public function clear(string $key): void;

    /**
     * Timestamp Unix quando o contador expira (null se não existir).
     */
    public function availableAt(string $key): ?int;
}
