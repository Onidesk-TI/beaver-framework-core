<?php

declare(strict_types=1);

namespace Beaver\Storage\Drivers;

use Beaver\Storage\Contracts\DiskDriver;

class NullDriver implements DiskDriver
{
    public function put(string $path, mixed $contents): bool { return false; }
    public function get(string $path): ?string { return null; }
    public function exists(string $path): bool { return false; }
    public function delete(string $path): bool { return false; }
    public function size(string $path): ?int { return null; }
    public function lastModified(string $path): ?int { return null; }
    public function files(string $directory = ''): array { return []; }
    public function directories(string $directory = ''): array { return []; }
    public function makeDirectory(string $path): bool { return false; }
    public function deleteDirectory(string $path): bool { return false; }
    public function path(string $path = ''): string { return ''; }
    public function url(string $path = ''): ?string { return null; }
}
