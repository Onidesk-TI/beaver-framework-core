<?php

declare(strict_types=1);

namespace Beaver\Cache\Drivers;

use Beaver\Cache\Contracts\CacheDriver;

/**
 * Driver de cache baseado em ficheiros.
 *
 * Cada chave vira um ficheiro: storage/cache/<hash>.cache
 * Formato: <expira_em_unix>\n<serialized_value>
 */
class FileDriver implements CacheDriver
{
    public function __construct(
        private string $path,
        private string $prefix = 'beaver_cache_',
    ) {
        if (!is_dir($this->path)) {
            @mkdir($this->path, 0755, true);
        }
    }

    public function put(string $key, mixed $value, ?int $ttl = null): void
    {
        $file = $this->fileFor($key);

        $expiresAt = $ttl === null ? 0 : time() + $ttl;

        $payload = $expiresAt . "\n" . serialize($value);

        @file_put_contents($file, $payload, LOCK_EX);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->fileFor($key);

        if (!is_file($file)) {
            return $default;
        }

        $content = @file_get_contents($file);

        if ($content === false || $content === '') {
            return $default;
        }

        $pos = strpos($content, "\n");

        if ($pos === false) {
            return $default;
        }

        $expiresAt = (int) substr($content, 0, $pos);
        $payload   = substr($content, $pos + 1);

        // Expirado?
        if ($expiresAt !== 0 && $expiresAt < time()) {
            @unlink($file);
            return $default;
        }

        $value = @unserialize($payload);

        return $value === false && $payload !== serialize(false)
            ? $default
            : $value;
    }

    public function has(string $key): bool
    {
        return $this->get($key, '__miss__') !== '__miss__';
    }

    public function forget(string $key): void
    {
        $file = $this->fileFor($key);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    public function flush(): void
    {
        foreach (glob($this->path . '/' . $this->prefix . '*.cache') ?: [] as $file) {
            @unlink($file);
        }
    }

    private function fileFor(string $key): string
    {
        return $this->path . '/' . $this->prefix . hash('sha256', $key) . '.cache';
    }
}
