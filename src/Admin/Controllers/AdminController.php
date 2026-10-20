<?php

declare(strict_types=1);

namespace Beaver\Admin\Controllers;

use Beaver\Admin\AdminLayout;
use Beaver\Foundation\Application;
use Beaver\Http\Request;
use Beaver\Http\Response;

class AdminController
{
    public function __construct(
        private ?Application $app = null,
    ) {
        $this->app ??= Application::getInstance();
    }

    /** GET /admin — dashboard simples */
    public function index(Request $request): Response
    {
        $content = <<<HTML
        <h1 style="margin:0 0 .3rem">Dashboard</h1>
        <p style="color:#6b5544;margin:0 0 2rem">Bem-vindo ao Beaver Admin.</p>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1rem">
            <a href="/admin/plugins" style="display:block;padding:1.2rem;background:#fff;border:1px solid #eadfc7;border-radius:10px;text-decoration:none;color:#2E1A10">
                <div style="font-size:1.5rem;color:#c25a1f"><i class="fas fa-plug"></i></div>
                <strong style="display:block;margin-top:.5rem">Plugins</strong>
                <small style="color:#8a7560">Gerir plugins instalados</small>
            </a>
            <a href="/admin/translations" style="display:block;padding:1.2rem;background:#fff;border:1px solid #eadfc7;border-radius:10px;text-decoration:none;color:#2E1A10">
                <div style="font-size:1.5rem;color:#c25a1f"><i class="fas fa-language"></i></div>
                <strong style="display:block;margin-top:.5rem">Traduções</strong>
                <small style="color:#8a7560">Editar traduções do projeto</small>
            </a>
        </div>
        HTML;

        $layout = new AdminLayout($this->app);
        return Response::html(
            $layout->render('Dashboard', $content, '/admin')
        );
    }
}
