<?php

declare(strict_types=1);

namespace Beaver\Storage\Drivers;

use Beaver\Storage\Contracts\DiskDriver;

/**
 * Driver de disco local (filesystem).
 */
class LocalDriver implements DiskDriver
{
    public function __construct(
        private string $root,
        private ?string $url = null,
        private int $permissions = 0755,
    ) {
        if (!is_dir($this->root)) {
            @mkdir($this->root, $this->permissions, true);
        }
    }

    public function put(string $path, mixed $contents): bool
    {
        $full = $this->path($path);

        $dir = dirname($full);
        if (!is_dir($dir) && !@mkdir($dir, $this->permissions, true)) {
            return false;
        }

        return @file_put_contents($full, $contents, LOCK_EX) !== false;
    }

    public function get(string $path): ?string
    {
        $full = $this->path($path);

        if (!is_file($full)) {
            return null;
        }

        $content = @file_get_contents($full);

        return $content === false ? null : $content;
    }

    public function exists(string $path): bool
    {
        return file_exists($this->path($path));
    }

    public function delete(string $path): bool
    {
        $full = $this->path($path);

        if (!is_file($full)) {
            return false;
        }

        return @unlink($full);
    }

    public function size(string $path): ?int
    {
        $full = $this->path($path);

        if (!is_file($full)) {
            return null;
        }

        $size = @filesize($full);

        return $size === false ? null : $size;
    }

    public function lastModified(string $path): ?int
    {
        $full = $this->path($path);

        if (!is_file($full)) {
            return null;
        }

        $time = @filemtime($full);

        return $time === false ? null : $time;
    }

    public function files(string $directory = ''): array
    {
        $full = $this->path($directory);

        if (!is_dir($full)) {
            return [];
        }

        $out = [];
        foreach (scandir($full) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            if (is_file($full . '/' . $item)) {
                $out[] = $directory === '' ? $item : $directory . '/' . $item;
            }
        }

        return $out;
    }

    public function directories(string $directory = ''): array
    {
        $full = $this->path($directory);

        if (!is_dir($full)) {
            return [];
        }

        $out = [];
        foreach (scandir($full) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            if (is_dir($full . '/' . $item)) {
                $out[] = $directory === '' ? $item : $directory . '/' . $item;
            }
        }

        return $out;
    }

    public function makeDirectory(string $path): bool
    {
        $full = $this->path($path);

        if (is_dir($full)) {
            return true;
        }

        return @mkdir($full, $this->permissions, true);
    }

    public function deleteDirectory(string $path): bool
    {
        $full = $this->path($path);

        if (!is_dir($full)) {
            return false;
        }

        foreach (scandir($full) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $full . '/' . $item;

            if (is_dir($itemPath)) {
                $this->deleteDirectory($path . '/' . $item);
            } else {
                @unlink($itemPath);
            }
        }

        return @rmdir($full);
    }

    public function path(string $path = ''): string
    {
        $path = ltrim($path, '/\\');

        if ($path === '') {
            return $this->root;
        }

        // Proteção contra path traversal
        if (str_contains($path, '..')) {
            throw new \InvalidArgumentException("Invalid path: {$path}");
        }

        return $this->root . '/' . $path;
    }

    public function url(string $path = ''): ?string
    {
        if ($this->url === null) {
            return null;
        }

        return rtrim($this->url, '/') . '/' . ltrim($path, '/');
    }
}
