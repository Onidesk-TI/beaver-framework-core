<?php

declare(strict_types=1);

namespace Beaver\Logging;

use Beaver\Logging\Contracts\LogDriver;
use Beaver\Logging\Drivers\FileDriver;
use Beaver\Logging\Drivers\NullDriver;
use Beaver\Logging\Drivers\StackDriver;

class LogFactory
{
    public static function fromConfig(array $config): LogDriver
    {
        $driver = $config['driver'] ?? 'file';

        return match ($driver) {
            'file' => new FileDriver(
                $config['file']['path'] ?? dirname(__DIR__, 2) . '/storage/logs',
                $config['file']['prefix'] ?? 'beaver',
            ),
            'null' => new NullDriver(),
            'stack' => self::stackFromConfig($config),
            default => throw new \InvalidArgumentException("Unknown log driver: {$driver}"),
        };
    }

    private static function stackFromConfig(array $config): StackDriver
    {
        $stack = new StackDriver();

        foreach ($config['stack']['channels'] ?? [] as $channelConfig) {
            $stack->add(self::fromConfig($channelConfig));
        }

        return $stack;
    }
}
