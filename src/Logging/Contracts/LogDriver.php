<?php

declare(strict_types=1);

namespace Beaver\Logging\Contracts;

interface LogDriver
{
    /**
     * Escreve uma entrada de log.
     *
     * @param string $level   Nível (DEBUG, INFO, WARNING, ERROR, CRITICAL)
     * @param string $message Mensagem
     * @param array  $context Contexto extra (arrays, IDs, etc.)
     */
    public function log(string $level, string $message, array $context = []): void;
}
