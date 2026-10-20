<?php

declare(strict_types=1);

namespace Beaver\Storage;

use Beaver\Storage\Contracts\DiskDriver;
use Beaver\Storage\Drivers\LocalDriver;
use Beaver\Storage\Drivers\NullDriver;

class StorageFactory
{
    public static function fromConfig(array $config): Filesystem
    {
        $default = $config['default'] ?? 'local';
        $disks   = [];

        foreach ($config['disks'] ?? [] as $name => $diskConfig) {
            $disks[$name] = self::makeDisk($diskConfig);
        }

        return new Filesystem($disks, $default);
    }

    private static function makeDisk(array $config): DiskDriver
    {
        $driver = $config['driver'] ?? 'local';

        return match ($driver) {
            'local' => new LocalDriver(
                $config['root'] ?? throw new \InvalidArgumentException('Disk "local" requires a root path'),
                $config['url'] ?? null,
                $config['permissions'] ?? 0755,
            ),
            'null' => new NullDriver(),
            default => throw new \InvalidArgumentException("Unknown disk driver: {$driver}"),
        };
    }
}
