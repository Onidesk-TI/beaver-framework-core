<?php
declare(strict_types=1);

namespace Beaver\Plugins\Cursos;

use Beaver\Plugin\PluginBase;

class CursosPlugin extends PluginBase
{
    public function boot(): void
    {
        $this->loadViews();
        $this->loadRoutes();
        $this->loadMigrations();

        // Sincroniza cursos dos plugins instalados (com cache por mtime)
        $this->syncCursosIfNeeded();
    }

    private function syncCursosIfNeeded(): void
    {
        $base = dirname($this->path);                // .../plugins
        $lock = $this->path . '/storage/.last-sync';

        // mtime mais recente dos curso.json
        $latest = 0;
        foreach (glob($base . '/*/curso.json') ?: [] as $f) {
            $latest = max($latest, (int)filemtime($f));
        }

        $last = is_file($lock) ? (int)file_get_contents($lock) : 0;
        if ($latest <= $last) return;

        try {
            $loader = new CourseLoader([$base]);
            $loader->syncAll();

            @mkdir(dirname($lock), 0775, true);
            @file_put_contents($lock, (string)$latest);
        } catch (\Throwable $e) {
            error_log('[beaver-cursos] sync falhou: ' . $e->getMessage());
        }
    }
}
