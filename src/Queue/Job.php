<?php

declare(strict_types=1);

namespace Beaver\Queue;

/**
 * Classe base para todos os jobs.
 *
 * Uso:
 *
 *     class SendWelcomeEmail extends Job
 *     {
 *         public function __construct(public int $userId) {}
 *
 *         public function handle(): void
 *         {
 *             $user = User::find($this->userId);
 *             mail_send($user->email, 'Bem-vindo', '...');
 *         }
 *     }
 *
 *     dispatch(new SendWelcomeEmail(42));
 */
abstract class Job
{
    /**
     * Lógica do job. Obrigatório.
     */
    abstract public function handle(): void;

    /**
     * Máximo de tentativas (default: 3).
     */
    public function maxAttempts(): int
    {
        return 3;
    }

    /**
     * Segundos de espera antes de re-tentar (default: 60).
     */
    public function backoff(): int
    {
        return 60;
    }

    /**
     * Fila onde corre (default: 'default').
     */
    public function queue(): string
    {
        return 'default';
    }

    /**
     * Devolve as propriedades públicas serializáveis.
     */
    public function payloadData(): array
    {
        $reflection = new \ReflectionClass($this);
        $data       = [];

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            $data[$prop->getName()] = $prop->getValue($this);
        }

        return $data;
    }
}
