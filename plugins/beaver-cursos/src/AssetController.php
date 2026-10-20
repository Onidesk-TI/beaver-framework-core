<?php

declare(strict_types=1);

namespace Beaver\Plugins\Cursos;

use Beaver\Http\Request;
use Beaver\Http\Response;

class AssetController
{
    private string $uiPath;

    public function __construct()
    {
        $this->uiPath = dirname(__DIR__) . '/resources/ui';
    }

    public function css(): Response
    {
        return $this->serve('css/curso.css', 'text/css; charset=utf-8');
    }

    public function js(): Response
    {
        return $this->serve('js/curso.js', 'application/javascript; charset=utf-8');
    }

    public function img(Request $request, string $file): Response
    {
        $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'webp'        => 'image/webp',
            'svg'         => 'image/svg+xml',
            'gif'         => 'image/gif',
            default       => 'application/octet-stream',
        };
        return $this->serve('img/' . $file, $mime);
    }

    private function serve(string $relative, string $mime): Response
    {
        $file = $this->uiPath . '/' . $relative;

        if (!is_file($file)) {
            return Response::text('Not found: ' . $relative, 404);
        }

        return (new Response((string)file_get_contents($file)))
            ->withHeader('Content-Type', $mime)
            ->withHeader('Cache-Control', 'public, max-age=3600');
    }
}
