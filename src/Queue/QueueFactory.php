<?php

declare(strict_types=1);

namespace Beaver\Queue;

use Beaver\Database\Database;
use Beaver\Queue\Contracts\QueueDriver;
use Beaver\Queue\Drivers\ArrayDriver;
use Beaver\Queue\Drivers\DatabaseDriver;
use Beaver\Queue\Drivers\SyncDriver;

class QueueFactory
{
    public static function fromConfig(array $config, ?Database $db = null): QueueDriver
    {
        $driver = $config['driver'] ?? 'sync';

        return match ($driver) {
            'sync'     => new SyncDriver(),
            'array'    => new ArrayDriver(),
            'database' => new DatabaseDriver(
                $db ?? Database::getInstance(),
                $config['table'] ?? 'jobs',
            ),
            default => throw new \InvalidArgumentException("Unknown queue driver: {$driver}"),
        };
    }
}
