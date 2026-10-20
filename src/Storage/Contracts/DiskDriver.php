<?php

declare(strict_types=1);

namespace Beaver\Storage\Contracts;

interface DiskDriver
{
    public function put(string $path, mixed $contents): bool;
    public function get(string $path): ?string;
    public function exists(string $path): bool;
    public function delete(string $path): bool;
    public function size(string $path): ?int;
    public function lastModified(string $path): ?int;

    /** @return array<int, string> */
    public function files(string $directory = ''): array;

    /** @return array<int, string> */
    public function directories(string $directory = ''): array;

    public function makeDirectory(string $path): bool;
    public function deleteDirectory(string $path): bool;

    public function path(string $path = ''): string;
    public function url(string $path = ''): ?string;
}
