<?php

declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Http;

use Beaver\Http\Response;
use Beaver\Foundation\Application;

class PricingController
{
    public function index(): Response
    {
        $viewPath = Application::getInstance()->basePath
            . '/plugins/frankei/resources/views/precos.php';

        if (!is_file($viewPath)) {
            return Response::text('View não encontrada: precos.php', 500);
        }

        ob_start();
        require $viewPath;
        return Response::html((string) ob_get_clean());
    }
}
