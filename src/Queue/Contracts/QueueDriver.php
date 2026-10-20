<?php

declare(strict_types=1);

namespace Beaver\Queue\Contracts;

use Beaver\Queue\JobPayload;

interface QueueDriver
{
    /**
     * Enfileira um job para execução imediata.
     */
    public function push(JobPayload $payload, string $queue = 'default'): void;

    /**
     * Enfileira um job para execução diferida (delay em segundos).
     */
    public function later(int $delaySeconds, JobPayload $payload, string $queue = 'default'): void;

    /**
     * Retira o próximo job disponível (ou null).
     */
    public function pop(string $queue = 'default'): ?JobPayload;

    /**
     * Marca como concluído (remove da fila).
     */
    public function delete(int|string $id): void;

    /**
     * Marca como falhado (guarda erro).
     */
    public function fail(int|string $id, string $error): void;

    /**
     * Reincrementa tentativas (para retry).
     */
    public function release(int|string $id, int $delaySeconds): void;

    /**
     * Tamanho atual da fila.
     */
    public function size(string $queue = 'default'): int;

    /**
     * Lista jobs pendentes.
     *
     * @return array<int, JobPayload>
     */
    public function list(string $queue = 'default', int $limit = 50): array;

    /**
     * Limpa a fila (todos os jobs).
     */
    public function clear(?string $queue = null): int;
}
