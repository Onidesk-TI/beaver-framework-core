<?php

declare(strict_types=1);

/** @var \Beaver\Http\Router $router */

use App\Api\V1\Controllers\HealthController;
use App\Api\V1\Controllers\MarketplaceController;

$router->get('/api/v1/health', [HealthController::class, 'index']);
$router->get('/api/v1/ping',   [HealthController::class, 'ping']);

$router->get('/api/v1/marketplace/items',        [MarketplaceController::class, 'index']);
$router->get('/api/v1/marketplace/items/{slug}', [MarketplaceController::class, 'show']);
$router->get('/api/v1/marketplace/categories',   [MarketplaceController::class, 'categories']);
$router->get('/api/v1/marketplace/stats',        [MarketplaceController::class, 'stats']);

// ─── Cursos (público) ────────────────────────────────────
$router->get('/api/v1/courses',                 [\App\Api\V1\Controllers\CoursesController::class, 'index']);
$router->get('/api/v1/courses/{slug}',          [\App\Api\V1\Controllers\CoursesController::class, 'show']);
$router->get('/api/v1/courses/{slug}/questoes', [\App\Api\V1\Controllers\CoursesController::class, 'questoes']);
