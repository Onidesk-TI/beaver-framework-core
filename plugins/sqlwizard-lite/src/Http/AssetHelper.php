<?php

/**
 * SqlWizard Lite — Helper de assets.
 *
 * @package    Beaver\Plugins\SqlWizardLite\Http
 * @author     Franco <contact@beaverphp.com>
 * @copyright  2026 OniDesk
 * @license    GPL-3.0-or-later
 * @link       https://github.com/Onidesk-TI/sqlwizard-lite
 * @since      0.1.0
 */

declare(strict_types=1);

namespace Beaver\Plugins\SqlWizardLite\Http;

use Beaver\Http\Response;

final class AssetHelper
{
    public static function serve(string $basePath, string $folder, string $file): Response
    {
        if (
            !preg_match('/^[A-Za-z0-9_-]+$/', $folder) ||
            !preg_match('/^[A-Za-z0-9_.-]+$/', $file)
        ) {
            return Response::text('Forbidden', 403);
        }

        $path = $basePath . '/resources/ui/' . $folder . '/' . $file;
        if (!is_file($path)) {
            return Response::text('Not found: ' . $folder . '/' . $file, 404);
        }

        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'css'         => 'text/css; charset=utf-8',
            'js'          => 'application/javascript; charset=utf-8',
            'json'        => 'application/json; charset=utf-8',
            'svg'         => 'image/svg+xml',
            'png'         => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp'        => 'image/webp',
            'gif'         => 'image/gif',
            'ico'         => 'image/x-icon',
            default       => 'application/octet-stream',
        };

        return (new Response(file_get_contents($path)))
            ->withHeader('Content-Type', $mime);
    }
}
