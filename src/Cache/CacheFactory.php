<?php

declare(strict_types=1);

namespace Beaver\Cache;

use Beaver\Cache\Contracts\CacheDriver;
use Beaver\Cache\Drivers\ArrayDriver;
use Beaver\Cache\Drivers\FileDriver;
use Beaver\Cache\Drivers\NullDriver;

class CacheFactory
{
    public static function fromConfig(array $config): CacheDriver
    {
        $driver = $config['driver'] ?? 'file';

        return match ($driver) {
            'file' => new FileDriver(
                $config['file']['path'] ?? dirname(__DIR__, 2) . '/storage/cache',
                $config['file']['prefix'] ?? 'beaver_cache_',
            ),
            'array' => new ArrayDriver(),
            'null'  => new NullDriver(),
            default => throw new \InvalidArgumentException("Unknown cache driver: {$driver}"),
        };
    }
}
