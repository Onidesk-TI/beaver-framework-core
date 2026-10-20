<?php

declare(strict_types=1);

namespace Beaver\Logging\Drivers;

use Beaver\Logging\Contracts\LogDriver;

/**
 * Envia o log para vários drivers ao mesmo tempo.
 *   ex: ficheiro + stdout + Sentry (futuro)
 */
class StackDriver implements LogDriver
{
    /** @param array<int, LogDriver> $drivers */
    public function __construct(
        private array $drivers = [],
    ) {
    }

    public function add(LogDriver $driver): void
    {
        $this->drivers[] = $driver;
    }

    public function log(string $level, string $message, array $context = []): void
    {
        foreach ($this->drivers as $driver) {
            $driver->log($level, $message, $context);
        }
    }
}
