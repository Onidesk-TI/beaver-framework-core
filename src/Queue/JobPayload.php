<?php

declare(strict_types=1);

namespace Beaver\Queue;

/**
 * DTO que representa um job serializado na fila.
 */
final class JobPayload
{
    public function __construct(
        public readonly int|string|null $id,       // ID na BD (null para sync/array)
        public readonly string $class,             // Nome da classe do job
        public readonly array $data,               // Propriedades públicas do job
        public readonly string $queue,             // Nome da fila
        public readonly int $maxAttempts,          // Máximo de tentativas
        public readonly int $attempts,             // Tentativas já feitas
        public readonly int $availableAt,          // Timestamp em que fica disponível
        public readonly int $createdAt,            // Timestamp de criação
    ) {
    }

    /**
     * Reconstrói o job a partir do payload.
     */
    public function resolve(): Job
    {
        if (!class_exists($this->class)) {
            throw new \RuntimeException("Job class not found: {$this->class}");
        }

        $instance = new ($this->class)(...$this->data);

        if (!$instance instanceof Job) {
            throw new \RuntimeException("Class {$this->class} does not extend Job");
        }

        return $instance;
    }

    /**
     * Serializa para array (guardar na BD).
     */
    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'class'        => $this->class,
            'data'         => $this->data,
            'queue'        => $this->queue,
            'max_attempts' => $this->maxAttempts,
            'attempts'     => $this->attempts,
            'available_at' => $this->availableAt,
            'created_at'   => $this->createdAt,
        ];
    }

    /**
     * Reconstrói a partir de dados da BD.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id:          $data['id']           ?? null,
            class:       $data['class']        ?? '',
            data:        $data['data']         ?? [],
            queue:       $data['queue']        ?? 'default',
            maxAttempts: (int) ($data['max_attempts'] ?? 3),
            attempts:    (int) ($data['attempts']     ?? 0),
            availableAt: (int) ($data['available_at'] ?? time()),
            createdAt:   (int) ($data['created_at']   ?? time()),
        );
    }
}
