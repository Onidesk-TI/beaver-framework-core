<?php

declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei;

use Beaver\Foundation\Application;

//GET /api/frankei/scan → sem cache? corre. com cache válida? devolve a cache.
//POST /api/frankei/scan → força scan (ignora cache).
class FrankeiCache
{
    private string $dir;

    public function __construct()
    {
        $this->dir = Application::getInstance()->basePath . '/storage/frankei-cache';
        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0775, true);
        }
    }

    private function key(string $connectionId, string $database, array $tables): string
    {
        $sig = $connectionId . '|' . $database . '|' . implode(',', $tables);
        return 'cache_' . substr(sha1($sig), 0, 16) . '.json';
    }

    private function path(string $connectionId, string $database, array $tables): string
    {
        return $this->dir . '/' . $this->key($connectionId, $database, $tables);
    }

    public function get(string $connectionId, string $database, array $tables, int $maxAgeSec = 300): ?array
    {
        $file = $this->path($connectionId, $database, $tables);
        if (!is_file($file)) {
            return null;
        }

        if (time() - filemtime($file) > $maxAgeSec) {
            @unlink($file);
            return null;
        }

        $raw = @file_get_contents($file);
        if ($raw === false) {
            return null;
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    public function put(string $connectionId, string $database, array $tables, array $result): void
    {
        $file = $this->path($connectionId, $database, $tables);
        @file_put_contents($file, json_encode($result));
    }

    public function invalidate(string $connectionId, string $database): void
    {
        // invalida todas as entradas para esta ligação/db
        $prefix = 'cache_';
        foreach (glob($this->dir . '/' . $prefix . '*.json') ?: [] as $file) {
            $raw = @file_get_contents($file);
            if (!$raw) {
                continue;
            }
            $data = json_decode($raw, true);
            if (!is_array($data)) {
                continue;
            }
            if (
                ($data['_meta']['connection_id'] ?? '') === $connectionId
                && ($data['_meta']['database'] ?? '') === $database
            ) {
                @unlink($file);
            }
        }
    }
}
