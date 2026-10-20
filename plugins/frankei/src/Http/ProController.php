<?php

declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Http;

use Beaver\Http\Response;
use Beaver\Foundation\Application;

class ProController
{
    public function index(): Response
    {
        $viewPath = Application::getInstance()->basePath
            . '/plugins/frankei/resources/views/pro.php';

        if (!is_file($viewPath)) {
            return Response::text('View não encontrada: pro.php', 500);
        }

        ob_start();
        require $viewPath;
        return Response::html((string) ob_get_clean());
    }
}
