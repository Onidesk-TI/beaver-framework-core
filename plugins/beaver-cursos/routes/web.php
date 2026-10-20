<?php

declare(strict_types=1);

/**
 * @var \Beaver\Http\Router $router
 * @var \Beaver\Plugin\PluginBase $plugin
 */

use Beaver\Plugins\Cursos\CursoController;
use Beaver\Plugins\Cursos\AssetController;

// ── Controllers ──────────────────────────
$curso  = new CursoController();
$asset  = new AssetController();

// ── Assets do plugin (css/js/img) ────────
$router->get('/plugins/beaver-cursos/ui/css/curso.css', [$asset, 'css']);
$router->get('/plugins/beaver-cursos/ui/js/curso.js', [$asset, 'js']);
$router->get('/plugins/beaver-cursos/ui/img/{file}', [$asset, 'img']);

// ── Rotas do curso ───────────────────────
$router->get('/cursos', [$curso, 'index']);
$router->get('/cursos/{slug}', [$curso, 'landing']);

$router->post('/cursos/login', [$curso, 'login']);
$router->post('/cursos/logout', [$curso, 'logout']);

$router->post('/cursos/iniciar', [$curso, 'iniciar']);
$router->get('/cursos/{slug}/teste', [$curso, 'teste']);
$router->post('/cursos/resposta', [$curso, 'resposta']);
$router->post('/cursos/finalizar', [$curso, 'finalizar']);
$router->get('/cursos/{slug}/resultado', [$curso, 'resultado']);
