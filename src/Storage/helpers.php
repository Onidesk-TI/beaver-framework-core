<?php

declare(strict_types=1);

use Beaver\Foundation\Application;
use Beaver\Storage\Contracts\DiskDriver;
use Beaver\Storage\Filesystem;

if (!function_exists('filesystem')) {
    function filesystem(): Filesystem
    {
        return Application::getInstance()->make(Filesystem::class);
    }
}

if (!function_exists('storage_disk')) {
    function storage_disk(?string $name = null): DiskDriver
    {
        return filesystem()->disk($name);
    }
}

if (!function_exists('storage_put')) {
    function storage_put(string $path, mixed $contents, ?string $disk = null): bool
    {
        return filesystem()->disk($disk)->put($path, $contents);
    }
}

if (!function_exists('storage_get')) {
    function storage_get(string $path, ?string $disk = null): ?string
    {
        return filesystem()->disk($disk)->get($path);
    }
}

if (!function_exists('storage_exists')) {
    function storage_exists(string $path, ?string $disk = null): bool
    {
        return filesystem()->disk($disk)->exists($path);
    }
}

if (!function_exists('storage_delete')) {
    function storage_delete(string $path, ?string $disk = null): bool
    {
        return filesystem()->disk($disk)->delete($path);
    }
}

if (!function_exists('storage_size')) {
    function storage_size(string $path, ?string $disk = null): ?int
    {
        return filesystem()->disk($disk)->size($path);
    }
}

if (!function_exists('storage_files')) {
    function storage_files(string $directory = '', ?string $disk = null): array
    {
        return filesystem()->disk($disk)->files($directory);
    }
}

if (!function_exists('storage_url')) {
    function storage_url(string $path, ?string $disk = null): ?string
    {
        return filesystem()->disk($disk)->url($path);
    }
}

if (!function_exists('storage_path_full')) {
    function storage_path_full(string $path = '', ?string $disk = null): string
    {
        return filesystem()->disk($disk)->path($path);
    }
}
