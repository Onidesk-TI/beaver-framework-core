<?php

/**
 * Beaver Framework — Rotas HTTP.
 */

declare(strict_types=1);

use App\Controllers\ForumController;

// ─── Helpers de caminhos (dinâmicos, funcionam em qualquer instalação) ───
if (!function_exists('framework_root')) {
    function framework_root(): string
    {
        $dir = dirname(__DIR__);
        for ($i = 0; $i < 6; $i++) {
            if (is_dir($dir . '/app')) {
                return $dir;
            }
            $dir = dirname($dir);
        }
        return rtrim(getenv('BEAVER_ROOT') ?: getcwd(), '/');
    }
}

if (!function_exists('mobile_projects_dir')) {
    /**
     * Encontra o caminho real de uma app, em qualquer sitio:
     *   1. app/users/{id}/mobile/{slug}   (user autenticado)
     *   2. app/users/{id}/mobile/{slug}   (qualquer user — PWA publico)
     *   3. app/mobile/{slug}              (legado)
     * Devolve null se nao encontrar.
     */
    function mobile_find_app_dir(string $slug): ?string
    {
        $slug = preg_replace('/[^a-z0-9_-]/', '', $slug);
        if ($slug === '') {
            return null;
        }

        // 1. Pasta do user autenticado (mais rapido)
        try {
            $uid = class_exists('\\Beaver\\Auth\\Auth') ? \Beaver\Auth\Auth::id() : null;
            if ($uid !== null && $uid !== '') {
                $dir = framework_root() . '/app/users/' . (int) $uid . '/mobile/' . $slug;
                if (is_dir($dir)) {
                    return $dir;
                }
            }
        } catch (\Throwable $e) {
        }

        // 2. Procurar em todos os users (PWA publico)
        $usersRoot = framework_root() . '/app/users';
        if (is_dir($usersRoot)) {
            foreach (glob($usersRoot . '/*/mobile/' . $slug, GLOB_ONLYDIR) ?: [] as $dir) {
                if (is_dir($dir)) {
                    return $dir;
                }
            }
        }

        // 3. Legado
        $legacy = framework_root() . '/app/mobile/' . $slug;
        if (is_dir($legacy)) {
            return $legacy;
        }

        return null;
    }

    function mobile_projects_dir(): string
    {
        // Preferir pasta do utilizador autenticado
        $uid = null;
        try {
            if (class_exists('\\Beaver\\Auth\\Auth')) {
                $uid = \Beaver\Auth\Auth::id();
            }
        } catch (\Throwable $e) {
            $uid = null;
        }

        // Utilizador autenticado -> app/users/{id}/mobile/
        if ($uid !== null && $uid !== '') {
            $dir = framework_root() . '/app/users/' . (int) $uid . '/mobile';
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            return $dir;
        }

        // Fallback legado (sem auth, para compatibilidade)
        return framework_root() . '/app/mobile';
    }
}

use Beaver\Http\Response;
use Beaver\Http\Router;
use Beaver\Http\Middleware\RequireAuth;
use Beaver\Admin\Controllers\AdminController;

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$router = Router::current();


try {
    $loja_destaque = array_slice(array_values(\App\Services\LojaService::produtos()), 0, 4);
} catch (\Throwable $e) {
    $loja_destaque = [];
}

// ═════════════════════════════════════════════════════════════
// PÁGINAS PRINCIPAIS
// ═════════════════════════════════════════════════════════════

$router->get('/', function () {
    $view = dirname(__DIR__) . '/app/views/index.php';
    if (!is_file($view)) {
        return Response::html('<h1>Beaver Framework v' . beaver_version() . '</h1>');
    }

    // Produtos em destaque para a home
    try {
        $loja_destaque = array_slice(array_values(\App\Services\LojaService::produtos()), 0, 4);
    } catch (\Throwable $e) {
        $loja_destaque = [];
    }
    ob_start();
    require $view;
    return Response::html(ob_get_clean());
});

$router->get('/app-sqlwizard', function () {
    $view = dirname(__DIR__) . '/app/views/app-sqlwizard.php';
    if (!is_file($view)) {
        return Response::text('View app-sqlwizard não encontrada', 404);
    }
    ob_start();
    require $view;
    return Response::html(ob_get_clean());
});

// FK_SQLWIZARD_LITE — alias /sqlwizard-lite (o menu chama "SqlWizard Lite")
$router->get('/sqlwizard-lite', function () {
    $view = dirname(__DIR__) . '/app/views/app-sqlwizard.php';
    if (!is_file($view)) {
        return Response::text('View app-sqlwizard não encontrada', 404);
    }
    ob_start();
    require $view;
    return Response::html(ob_get_clean());
});

$router->get('/php-sdk', function () {
    $view = dirname(__DIR__) . '/app/views/php-sdk.php';
    if (!is_file($view)) {
        return Response::text('View php-sdk não encontrada', 404);
    }
    ob_start();
    require $view;
    return Response::html(ob_get_clean());
});

$router->get('/marketplace', function () {
    $view = dirname(__DIR__) . '/app/views/marketplace.php';
    if (!is_file($view)) {
        return Response::text('View marketplace não encontrada', 404);
    }
    ob_start();
    require $view;
    return Response::html(ob_get_clean());
});

$router->get('/plugins', function () {
    $view = dirname(__DIR__) . '/app/views/plugins.php';
    if (!is_file($view)) {
        return Response::text('View plugins não encontrada', 404);
    }
    ob_start();
    require $view;
    return Response::html(ob_get_clean());
});

$router->get('/hello', function () {
    return Response::html('<h1>HELLO — v' . beaver_version() . '</h1>');
});

$router->get('/ping', function () {
    return Response::json([
        'ok'      => true,
        'time'    => date('c'),
        'version' => beaver_version(),
    ]);
});

// ═════════════════════════════════════════════════════════════
// ASSETS UI GLOBAIS
// ═════════════════════════════════════════════════════════════

$router->get('/img/{file}', function ($req, $file) {
    $file = (string) $file;
    if (!preg_match('/^[A-Za-z0-9_.-]+\.(png|jpg|jpeg|gif|webp|svg|ico)$/i', $file)) {
        return Response::text('Forbidden', 403);
    }
    $path = dirname(__DIR__) . '/resources/ui/img/' . $file;
    if (!is_file($path)) {
        return Response::text('Not found: ' . $file, 404);
    }
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mime = match ($ext) {
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'ico' => 'image/x-icon',
        default => 'application/octet-stream',
    };
    return (new Response(file_get_contents($path)))
        ->withHeader('Content-Type', $mime)
        ->withHeader('Cache-Control', 'public, max-age=3600');
});

$router->get('/css/{file}', function ($req, $file) {
    $file = (string) $file;
    if (!preg_match('/^[A-Za-z0-9_.-]+\.css$/', $file)) {
        return Response::text('Forbidden', 403);
    }
    $path = dirname(__DIR__) . '/resources/ui/css/' . $file;
    if (!is_file($path)) {
        return Response::text('/* not found: ' . $file . ' */', 404);
    }
    return (new Response(file_get_contents($path)))
        ->withHeader('Content-Type', 'text/css; charset=utf-8')
        ->withHeader('Cache-Control', 'public, max-age=3600');
});

$router->get('/js/{file}', function ($req, $file) {
    $file = (string) $file;
    if (!preg_match('/^[A-Za-z0-9_.-]+\.js$/', $file)) {
        return Response::text('Forbidden', 403);
    }
    $path = dirname(__DIR__) . '/resources/ui/js/' . $file;
    if (!is_file($path)) {
        return Response::text('// not found: ' . $file, 404);
    }
    return (new Response(file_get_contents($path)))
        ->withHeader('Content-Type', 'application/javascript; charset=utf-8')
        ->withHeader('Cache-Control', 'public, max-age=3600');
});

// ═════════════════════════════════════════════════════════════
// TEMAS
// ═════════════════════════════════════════════════════════════

$router->get('/theme-preview/{slug}', function ($req, $slug) {
    $slug = (string) $slug;
    if (!preg_match('/^[a-z][a-z0-9_-]*$/', $slug)) {
        return Response::text('Slug inválido', 400);
    }
    $mgr = app()->make(\Beaver\Sdk\Theme\ThemeManager::class);
    $dir = $mgr->path($slug);
    if ($dir === null) {
        return Response::text("Tema não encontrado: $slug", 404);
    }
    $viewFile = $dir . '/resources/views/index.php';
    if (!is_file($viewFile)) {
        return Response::text("View 'index' não encontrada no tema $slug", 404);
    }
    ob_start();
    require $viewFile;
    return Response::html(ob_get_clean());
});

if (!function_exists('beaver_theme_serve_asset')) {
    function beaver_theme_serve_asset($mgr, string $slug, string $kind, string $file): Response
    {
        if (!preg_match('/^[a-z][a-z0-9_-]*$/', $slug)) {
            return Response::text('Forbidden', 403);
        }
        if (!in_array($kind, ['css', 'js'], true)) {
            return Response::text('Forbidden', 403);
        }
        if (!preg_match('/^[A-Za-z0-9_.-]+$/', $file)) {
            return Response::text('Forbidden', 403);
        }
        $base = $mgr->path($slug);
        if ($base === null) {
            return Response::text('Theme not found', 404);
        }
        $path = $base . '/resources/ui/' . $kind . '/' . $file;
        if (!is_file($path)) {
            return Response::text('Not found: ' . $kind . '/' . $file, 404);
        }
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $mime = match ($ext) {
            'css' => 'text/css; charset=utf-8',
            'js' => 'application/javascript; charset=utf-8',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'woff2' => 'font/woff2',
            'woff' => 'font/woff',
            'ttf' => 'font/ttf',
            default => 'application/octet-stream',
        };
        return (new Response(file_get_contents($path)))
            ->withHeader('Content-Type', $mime)
            ->withHeader('Cache-Control', 'public, max-age=3600');
    }
}

$router->get('/themes/{slug}/css/{file}', function ($req, $slug, $file) {
    $mgr = app()->make(\Beaver\Sdk\Theme\ThemeManager::class);
    return beaver_theme_serve_asset($mgr, (string) $slug, 'css', (string) $file);
});

$router->get('/themes/{slug}/js/{file}', function ($req, $slug, $file) {
    $mgr = app()->make(\Beaver\Sdk\Theme\ThemeManager::class);
    return beaver_theme_serve_asset($mgr, (string) $slug, 'js', (string) $file);
});

// ═════════════════════════════════════════════════════════════
// DEBUG
// ═════════════════════════════════════════════════════════════

$router->get('/debug-request', function () {
    return Response::text(
        "REQUEST_URI:  " . ($_SERVER['REQUEST_URI']  ?? '?') . "\n" .
            "PATH_INFO:    " . ($_SERVER['PATH_INFO']    ?? '?') . "\n" .
            "SCRIPT_NAME:  " . ($_SERVER['SCRIPT_NAME']  ?? '?') . "\n" .
            "QUERY_STRING: " . ($_SERVER['QUERY_STRING'] ?? '?') . "\n"
    );
});

// ═════════════════════════════════════════════════════════════
// EXEMPLOS
// ═════════════════════════════════════════════════════════════

$router->get('/exemplos', function () {
    $view = dirname(__DIR__) . '/app/views/exemplos/index.php';
    if (!is_file($view)) {
        return Response::text('View de exemplos não encontrada', 404);
    }
    ob_start();
    require $view;
    return Response::html(ob_get_clean());
});

$router->get('/exemplos/{slug}', function ($req, $slug) {
    $slug = (string) $slug;
    if (!preg_match('/^[a-z0-9_-]+$/', $slug)) {
        return Response::text('Slug inválido', 400);
    }
    $exemplos = require dirname(__DIR__) . '/resources/data/exemplos.php';
    if (!isset($exemplos[$slug])) {
        return Response::text('Exemplo não encontrado: ' . $slug, 404);
    }
    $view = dirname(__DIR__) . '/app/views/exemplos/detalhe.php';
    if (!is_file($view)) {
        return Response::text('View de detalhe não encontrada', 404);
    }
    $exemplo = $exemplos[$slug];
    ob_start();
    require $view;
    return Response::html(ob_get_clean());
});

// ═════════════════════════════════════════════════════════════
// DOCS
// ═════════════════════════════════════════════════════════════

$router->get('/plugins-manager', function () {
    $view = dirname(__DIR__) . '/resources/docs/index.php';
    if (!is_file($view)) {
        return Response::text('Gerenciador nao encontrado', 404);
    }
    ob_start();
    require $view;
    return Response::html(ob_get_clean());
});

$router->get('/plugins-manager/css/{file}', function ($req, $file) {
    $file = (string) $file;
    if (!preg_match('/^[A-Za-z0-9_.-]+\.css$/', $file)) {
        return Response::text('Forbidden', 403);
    }
    $path = dirname(__DIR__) . '/resources/docs/' . $file;
    if (!is_file($path)) {
        return Response::text('/* not found: ' . $file . ' */', 404);
    }
    return (new Response(file_get_contents($path)))
        ->withHeader('Content-Type', 'text/css; charset=utf-8')
        ->withHeader('Cache-Control', 'public, max-age=3600');
});

// ═════════════════════════════════════════════════════════════
// ADMIN
// ═════════════════════════════════════════════════════════════

$admin = new AdminController();
$router->get('/admin', [$admin, 'index'], [RequireAuth::class]);

// ═════════════════════════════════════════════════════════════
// PLUGIN: SQLWIZARD LITE
// ═════════════════════════════════════════════════════════════

$litePath = dirname(__DIR__) . '/plugins/sqlwizard-lite';
if (is_dir($litePath) && is_file($litePath . '/routes/web.php')) {
    $manifestFile = $litePath . '/plugin.json';
    $manifest = is_file($manifestFile) ? json_decode((string) file_get_contents($manifestFile), true) ?: [] : [];
    $plugin = new \Beaver\Plugins\SqlWizardLite\SqlWizardLitePlugin($litePath, $manifest);
    require $litePath . '/routes/web.php';
}

// ═════════════════════════════════════════════════════════════
// PLUGIN: MOBILE KIT
// ═════════════════════════════════════════════════════════════

$kitPath = dirname(__DIR__) . '/plugins/mobile-kit';
if (is_dir($kitPath) && is_file($kitPath . '/routes/web.php')) {
    $manifestFile = $kitPath . '/plugin.json';
    $manifest = is_file($manifestFile) ? json_decode((string) file_get_contents($manifestFile), true) ?: [] : [];
    $plugin = new \Beaver\Plugins\MobileKit\MobileKitPlugin($kitPath, $manifest);
    require $kitPath . '/routes/web.php';
}

// ═════════════════════════════════════════════════════════════
// AUTH
// ═════════════════════════════════════════════════════════════

use App\Controllers\AuthController;

$router->post('/forum/reply/{id}/apagar',  fn($req, $id) => (new ForumController())->apagarReply((int) $id));
$router->post('/forum/reply/{id}/aceitar', fn($req, $id) => (new ForumController())->aceitarReply((int) $id));
$router->post('/forum/votar',                    fn() => (new \App\Controllers\ForumController())->votar());
$router->get('/forum/reply/{id}/editar',  fn($req, $id) => (new \App\Controllers\ForumController())->editarReplyForm((int) $id));
$router->post('/forum/reply/{id}/editar',  fn($req, $id) => (new \App\Controllers\ForumController())->editarReply((int) $id));

use App\Controllers\PainelController;
use Beaver\Http\Middleware\RequireGuest;

$auth = new AuthController();
$router->get('/login', [$auth, 'showLogin'], [RequireGuest::class]);
$router->post('/login', [$auth, 'login']);
$router->post('/logout', [$auth, 'logout']);

// FK_ENTRAR — alias /entrar para /login (o .env aponta para /entrar)
$router->get('/entrar', [$auth, 'showLogin'], [RequireGuest::class]);
$router->post('/entrar', [$auth, 'login']);

// ═════════════════════════════════════════════════════════════
// DEBUG (remover em produção)
// ═════════════════════════════════════════════════════════════

$router->get('/debug-throw', function () {
    throw new \RuntimeException('Isto é um erro de teste — 500 propositado.');
});

$router->get('/debug-fatal', function () {
    return nao_existe_esta_funcao();
});

$router->get('/debug-warning', function () {
    return Response::html('Valor: ' . $variavel_que_nao_existe);
});

// ═════════════════════════════════════════════════════════════
// STORAGE
// ═════════════════════════════════════════════════════════════

$router->get('/storage/{path}', function ($req, $path) {
    if (str_contains($path, '..')) {
        return Response::text('Forbidden', 403);
    }
    $full = storage_path_full($path, 'public');
    if (!is_file($full)) {
        return Response::text('Not found', 404);
    }
    $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
    $mime = match ($ext) {
        'css' => 'text/css; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'pdf' => 'application/pdf',
        'txt' => 'text/plain; charset=utf-8',
        'zip' => 'application/zip',
        default => 'application/octet-stream',
    };
    return (new Response(file_get_contents($full)))
        ->withHeader('Content-Type', $mime)
        ->withHeader('Cache-Control', 'public, max-age=3600');
});

// ═════════════════════════════════════════════════════════════
// PLUGIN: MOBILE EDITOR
// ═════════════════════════════════════════════════════════════

$editorPath = dirname(__DIR__) . '/plugins/mobile-editor';
if (is_dir($editorPath) && is_file($editorPath . '/routes/web.php')) {
    $manifest = is_file($editorPath . '/plugin.json')
        ? json_decode((string) file_get_contents($editorPath . '/plugin.json'), true) ?: []
        : [];
    if (class_exists(\Beaver\Plugins\MobileEditor\MobileEditorPlugin::class)) {
        new \Beaver\Plugins\MobileEditor\MobileEditorPlugin($editorPath, $manifest);
    }
    require $editorPath . '/routes/web.php';
}

// ═════════════════════════════════════════════════════════════
// MOBILE-EDITOR: servir JS do plugin
// ═════════════════════════════════════════════════════════════

$router->get('/mobile-editor/js/{file}', function ($req, $file) {
    $file = (string) $file;
    if (!preg_match('/^[A-Za-z0-9_.-]+\.js$/', $file)) {
        return Response::text('Forbidden', 403);
    }
    $path = dirname(__DIR__) . '/plugins/mobile-editor/resources/js/' . $file;
    if (!is_file($path)) {
        return Response::text('// not found: ' . $file, 404);
    }
    return (new Response(file_get_contents($path)))
        ->withHeader('Content-Type', 'application/javascript; charset=utf-8')
        ->withHeader('Cache-Control', 'public, max-age=300');
});

// ═════════════════════════════════════════════════════════════
// MOBILE-EDITOR: criar / apagar / carregar / guardar projeto
// ═════════════════════════════════════════════════════════════

// ── Criar projeto novo ──
$router->post('/mobile-editor/api/project', function ($req) {
    $body    = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
    $slug    = strtolower(trim((string) ($body['slug'] ?? '')));
    $name    = trim((string) ($body['name'] ?? ''));
    $package = trim((string) ($body['package'] ?? ''));

    if (!preg_match('/^[a-z][a-z0-9_-]*$/', $slug)) {
        return Response::json(['ok' => false, 'error' => 'Slug inválido (começa por letra; só minúsculas, números, - e _)'], 400);
    }

    $mobileDir   = mobile_projects_dir();
    $projectPath = $mobileDir . '/' . $slug;

    if (is_dir($projectPath)) {
        return Response::json(['ok' => false, 'error' => 'Já existe um projeto com o slug "' . $slug . '"'], 409);
    }

    if (!is_dir($mobileDir) && !@mkdir($mobileDir, 0755, true)) {
        return Response::json(['ok' => false, 'error' => 'Erro a criar app/mobile'], 500);
    }
    if (!@mkdir($projectPath, 0755, true)) {
        return Response::json(['ok' => false, 'error' => 'Erro a criar pasta do projeto'], 500);
    }
    @mkdir($projectPath . '/screens', 0755, true);

    $app = [
        'name'         => $name !== '' ? $name : $slug,
        'slug'         => $slug,
        'version'      => '1.0.0',
        'package'      => $package !== '' ? $package : ('com.beaver.' . str_replace('-', '', $slug)),
        'theme'        => 'theme.json',
        'start_screen' => 'dashboard',
        'screens'      => ['dashboard'],
        'targets'      => ['pwa' => true, 'kotlin' => false, 'swift' => false, 'flutter' => false],
    ];
    @file_put_contents(
        $projectPath . '/app.json',
        json_encode($app, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );

    $dashboard = ['id' => 'dashboard', 'title' => 'Dashboard', 'type' => 'custom', 'components' => []];
    @file_put_contents(
        $projectPath . '/screens/dashboard.json',
        json_encode($dashboard, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );

    // ─── theme.json por defeito ───
    $theme = [
        'colors' => [
            'primary'      => '#F5A623',
            'primary_dark' => '#E07800',
            'accent'       => '#7A3F00',
            'background'   => '#F4F5F7',
            'surface'      => '#FFFFFF',
            'text'         => '#2B2F36',
            'text_soft'    => '#5F656D',
            'border'       => '#E4E6EA',
            'success'      => '#2E9B5C',
            'danger'       => '#C43B3B',
        ],
        'fonts'  => ['family' => 'Inter', 'mono' => 'JetBrains Mono'],
        'radius' => ['sm' => '8px', 'md' => '14px', 'lg' => '20px'],
    ];

    @file_put_contents(
        $projectPath . '/theme.json',
        json_encode($theme, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );

    return Response::json(['ok' => true, 'slug' => $slug, 'name' => $app['name'], 'path' => $projectPath]);
});

// ── GET project/{slug} — carregar projeto ──
$router->get('/mobile-editor/api/project/{slug}', function ($req, $slug) {
    $slug = (string) $slug;
    if (!preg_match('/^[a-z][a-z0-9_-]*$/', $slug)) {
        return Response::json(['ok' => false, 'error' => 'Slug inválido'], 400);
    }

    $projectPath = mobile_find_app_dir($slug) ?: (mobile_projects_dir() . '/' . $slug);
    if (!is_dir($projectPath)) {
        return Response::json(['ok' => false, 'error' => 'Projeto não encontrado'], 404);
    }

    $appFile = $projectPath . '/app.json';
    $app = is_file($appFile)
        ? json_decode((string) file_get_contents($appFile), true) ?: []
        : [];

    $screens = [];
    foreach (glob($projectPath . '/screens/*.json') ?: [] as $file) {
        $id = basename($file, '.json');
        $screens[$id] = json_decode((string) file_get_contents($file), true) ?: [];
    }

    return Response::json(['ok' => true, 'slug' => $slug, 'app' => $app, 'screens' => $screens]);
});

// ── DELETE project/{slug} — apagar (move para .trash) ──
$router->delete('/mobile-editor/api/project/{slug}', function ($req, $slug) {
    $slug = (string) $slug;
    if (!preg_match('/^[a-z][a-z0-9_-]*$/', $slug)) {
        return Response::json(['ok' => false, 'error' => 'Slug inválido'], 400);
    }

    $mobileDir   = mobile_projects_dir();
    $projectPath = $mobileDir . '/' . $slug;
    if (!is_dir($projectPath)) {
        return Response::json(['ok' => false, 'error' => 'Projeto não encontrado'], 404);
    }

    $trash = $mobileDir . '/.trash';
    @mkdir($trash, 0755, true);
    $dest = $trash . '/' . $slug . '_' . date('Ymd_His');

    if (!@rename($projectPath, $dest)) {
        return Response::json(['ok' => false, 'error' => 'Erro a mover para .trash'], 500);
    }

    return Response::json(['ok' => true, 'moved_to' => '.trash/' . basename($dest)]);
});

// ── POST project/{slug}/app — guardar Android ──
$router->post('/mobile-editor/api/project/{slug}/app', function ($req, $slug) {
    $slug = (string) $slug;
    if (!preg_match('/^[a-z][a-z0-9_-]*$/', $slug)) {
        return Response::json(['ok' => false, 'error' => 'Slug inválido'], 400);
    }

    $raw     = file_get_contents('php://input');
    $body    = json_decode($raw ?: '{}', true) ?: [];
    $android = $body['android'] ?? null;

    if (!is_array($android)) {
        return Response::json(['ok' => false, 'error' => 'Payload inválido'], 400);
    }

    $base        = framework_root();
    $mobileDir   = mobile_projects_dir();
    $projectPath = $mobileDir . '/' . $slug;

    if (!is_dir($projectPath)) {
        return Response::json([
            'ok'    => false,
            'error' => 'Projeto não encontrado: ' . $slug,
            'debug' => [
                'slug_recebido'          => $slug,
                'framework_root'         => $base,
                'mobileDir'              => $mobileDir,
                'mobileExists'           => is_dir($mobileDir),
                'projectPath'            => $projectPath,
                'projectExists'          => is_dir($projectPath),
                'projetos_em_app_mobile' => is_dir($mobileDir)
                    ? array_values(array_diff(scandir($mobileDir), ['.', '..']))
                    : [],
            ],
        ], 404);
    }

    $appJsonPath = $projectPath . '/app.json';
    $app = is_file($appJsonPath)
        ? json_decode((string) file_get_contents($appJsonPath), true) ?: []
        : [];

    $app['android'] = $android;

    $json = json_encode($app, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if (@file_put_contents($appJsonPath, $json) === false) {
        return Response::json(['ok' => false, 'error' => 'Erro a gravar app.json'], 500);
    }

    return Response::json(['ok' => true, 'file' => 'app.json']);
});

// ── POST project/{slug}/save — guardar telas ──
$router->post('/mobile-editor/api/project/{slug}/save', function ($req, $slug) {
    $slug = (string) $slug;
    if (!preg_match('/^[a-z][a-z0-9_-]*$/', $slug)) {
        return Response::json(['ok' => false, 'error' => 'Slug inválido'], 400);
    }

    $projectPath = mobile_find_app_dir($slug) ?: (mobile_projects_dir() . '/' . $slug);
    if (!is_dir($projectPath)) {
        return Response::json(['ok' => false, 'error' => 'Projeto não encontrado'], 404);
    }

    $body         = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
    $screens      = $body['screens']       ?? [];
    $startScreen  = $body['start_screen']  ?? null;
    $activeScreen = $body['active_screen'] ?? null;

    if (!is_array($screens) || empty($screens)) {
        return Response::json(['ok' => false, 'error' => 'Sem telas para guardar'], 400);
    }

    $screensDir = $projectPath . '/screens';
    @mkdir($screensDir, 0755, true);

    $backupDir = $screensDir . '/.bak';
    @mkdir($backupDir, 0755, true);
    $stamp = date('Ymd_H');

    $count = 0;
    $backups = [];
    foreach ($screens as $id => $data) {
        if (!preg_match('/^[a-z][a-z0-9_-]*$/', (string) $id)) {
            continue;
        }

        $file = $screensDir . '/' . $id . '.json';
        if (is_file($file)) {
            $bakFile = $backupDir . '/' . $id . '_' . $stamp . '.json';
            if (!is_file($bakFile)) {
                @copy($file, $bakFile);
                $backups[] = basename($bakFile);
            }
        }

        @file_put_contents(
            $file,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        $count++;
    }

    $target  = $startScreen ?: $activeScreen;
    $appFile = $projectPath . '/app.json';
    if (is_file($appFile)) {
        $app = json_decode((string) file_get_contents($appFile), true) ?: [];

        if ($target && preg_match('/^[a-z][a-z0-9_-]*$/', $target)) {
            $app['start_screen'] = $target;
        }

        $existing = $app['screens'] ?? [];
        foreach (array_keys($screens) as $id) {
            if (!in_array($id, $existing, true)) {
                $existing[] = $id;
            }
        }
        $app['screens'] = $existing;

        @file_put_contents(
            $appFile,
            json_encode($app, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    return Response::json(['ok' => true, 'count' => $count, 'backups' => $backups]);
});

// ── POST project/{slug}/start-screen — definir start_screen ──
$router->post('/mobile-editor/api/project/{slug}/start-screen', function ($req, $slug) {
    $slug = (string) $slug;
    if (!preg_match('/^[a-z][a-z0-9_-]*$/', $slug)) {
        return Response::json(['ok' => false, 'error' => 'Slug inválido'], 400);
    }

    $body   = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
    $screen = trim((string) ($body['screen'] ?? ''));
    if (!preg_match('/^[a-z][a-z0-9_-]*$/', $screen)) {
        return Response::json(['ok' => false, 'error' => 'Screen inválido'], 400);
    }

    $appBase = mobile_find_app_dir($slug) ?: (mobile_projects_dir() . '/' . $slug);
    $appFile = $appBase . '/app.json';
    if (!is_file($appFile)) {
        return Response::json(['ok' => false, 'error' => 'app.json não existe'], 404);
    }

    $app = json_decode((string) file_get_contents($appFile), true) ?: [];
    $app['start_screen'] = $screen;

    $app['screens'] = $app['screens'] ?? [];
    if (!in_array($screen, $app['screens'], true)) {
        $app['screens'][] = $screen;
    }

    @file_put_contents(
        $appFile,
        json_encode($app, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );

    return Response::json(['ok' => true, 'start_screen' => $screen]);
});

// ═════════════════════════════════════════════════════════════
// MOBILE-EDITOR: criar / apagar telas
// ═════════════════════════════════════════════════════════════

$router->post('/mobile-editor/api/project/{slug}/screen', function ($req, $slug) {
    $slug = (string) $slug;
    if (!preg_match('/^[a-z][a-z0-9_-]*$/', $slug)) {
        return Response::json(['ok' => false, 'error' => 'Slug inválido'], 400);
    }

    $projectPath = mobile_find_app_dir($slug) ?: (mobile_projects_dir() . '/' . $slug);
    if (!is_dir($projectPath)) {
        return Response::json(['ok' => false, 'error' => 'Projeto não encontrado'], 404);
    }

    $body = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
    $id   = strtolower(trim((string) ($body['id'] ?? '')));
    $data = $body['data'] ?? null;

    if (!preg_match('/^[a-z][a-z0-9_-]*$/', $id) || !is_array($data)) {
        return Response::json(['ok' => false, 'error' => 'ID ou dados inválidos'], 400);
    }

    $file = $projectPath . '/screens/' . $id . '.json';
    if (is_file($file)) {
        return Response::json(['ok' => false, 'error' => 'Tela já existe'], 409);
    }

    @mkdir($projectPath . '/screens', 0755, true);
    @file_put_contents(
        $file,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );

    return Response::json(['ok' => true, 'id' => $id]);
});

$router->delete('/mobile-editor/api/project/{slug}/screen/{id}', function ($req, $slug, $id) {
    $slug = (string) $slug;
    $id   = (string) $id;
    if (!preg_match('/^[a-z][a-z0-9_-]*$/', $slug) || !preg_match('/^[a-z][a-z0-9_-]*$/', $id)) {
        return Response::json(['ok' => false, 'error' => 'Slug ou ID inválido'], 400);
    }

    $projectPath = mobile_find_app_dir($slug) ?: (mobile_projects_dir() . '/' . $slug);
    $file = $projectPath . '/screens/' . $id . '.json';
    if (!is_file($file)) {
        return Response::json(['ok' => false, 'error' => 'Tela não encontrada'], 404);
    }

    $trash = $projectPath . '/screens/.trash';
    @mkdir($trash, 0755, true);
    @rename($file, $trash . '/' . $id . '_' . date('Ymd_His') . '.json');

    return Response::json(['ok' => true]);
});

// ═════════════════════════════════════════════════════════════
// MOBILE-EDITOR: server restart / status
// ═════════════════════════════════════════════════════════════

$router->post('/mobile-editor/api/server/restart', function ($req) {
    if (getenv('APP_ENV') === 'production') {
        return Response::json(['ok' => false, 'error' => 'Bloqueado em produção'], 403);
    }

    $root   = framework_root();
    $script = $root . '/serve.sh';
    $port   = (int) (getenv('DEV_PORT') ?: 9000);

    if (!is_file($script)) {
        return Response::json(['ok' => false, 'error' => 'serve.sh não encontrado'], 404);
    }

    @exec("ss -tlnp 2>/dev/null | grep ':$port ' | grep -oP 'pid=\\K[0-9]+' | head -1", $out, $rc);
    if (!empty($out[0])) {
        $pid = (int) $out[0];
        @exec("ps -p $pid -o comm= 2>/dev/null", $commOut);
        if (!empty($commOut[0]) && str_contains($commOut[0], 'php')) {
            @exec("kill $pid 2>/dev/null");
        }
    }

    $cmd = "cd " . escapeshellarg($root) . " && "
        . "nohup php -S 127.0.0.1:$port -t public "
        . "> php-server.log 2>&1 & echo \$!";

    $newPid = (int) trim((string) @shell_exec($cmd));

    return Response::json([
        'ok'      => true,
        'pid'     => $newPid,
        'port'    => $port,
        'message' => 'Servidor a arrancar em http://127.0.0.1:' . $port,
    ]);
});

$router->get('/mobile-editor/api/server/status', function () {
    $port = (int) (getenv('DEV_PORT') ?: 9000);
    @exec("ss -tlnp 2>/dev/null | grep ':$port ' | grep -oP 'pid=\\K[0-9]+' | head -1", $out);
    $pid = !empty($out[0]) ? (int) $out[0] : 0;

    return Response::json(['ok' => true, 'up' => $pid > 0, 'pid' => $pid, 'port' => $port]);
});

// ═════════════════════════════════════════════════════════════
// ─── Servir uploads: /m/{slug}/uploads/{file} ───
$router->get('/m/{slug}/uploads/{file}', function ($req, $slug, $file) {
    $slug = (string) $slug;
    $file = basename((string) $file);

    if (!preg_match('/^[a-z][a-z0-9_-]*$/', $slug)) {
        return Response::text('Slug inválido', 400);
    }
    if ($file === '' || !preg_match('/^[a-z0-9._-]+$/i', $file)) {
        return Response::text('Ficheiro inválido', 400);
    }

    // Encontrar o projeto
    $projectPath = function_exists('mobile_find_app_dir')
        ? mobile_find_app_dir($slug)
        : null;
    if (!$projectPath) {
        $projectPath = mobile_projects_dir() . '/' . $slug;
    }

    $full = $projectPath . '/uploads/' . $file;
    if (!is_file($full)) {
        return Response::text('Ficheiro não encontrado', 404);
    }

    // MIME type
    $mime = 'application/octet-stream';
    if (function_exists('mime_content_type')) {
        $m = @mime_content_type($full);
        if ($m) {
            $mime = $m;
        }
    } else {
        // Fallback por extensão
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $map = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'pdf' => 'application/pdf',
        ];
        if (isset($map[$ext])) {
            $mime = $map[$ext];
        }
    }

    $content = file_get_contents($full);
    if ($content === false) {
        return Response::text('Erro ao ler o ficheiro', 500);
    }

    $r = new Response($content);
    $r = $r->withHeader('Content-Type', $mime);
    $r = $r->withHeader('Content-Length', (string) strlen($content));
    $r = $r->withHeader('Cache-Control', 'public, max-age=86400');
    return $r;
});

// MULTI-APP: /m/{slug}/{screen}
// ═════════════════════════════════════════════════════════════

$router->get('/m/{slug}/manifest.json', function ($req, $slug) {
    $slug = (string) $slug;
    if (!preg_match('/^[a-z][a-z0-9_-]*$/', $slug)) {
        return Response::text('Slug inválido', 400);
    }

    $projectPath = mobile_find_app_dir($slug) ?: (mobile_projects_dir() . '/' . $slug);
    $appJson = is_file($projectPath . '/app.json')
        ? json_decode((string) file_get_contents($projectPath . '/app.json'), true) ?: []
        : [];

    return Response::json([
        'name'             => $appJson['name'] ?? $slug,
        'short_name'       => $appJson['short_name'] ?? $slug,
        'start_url'        => '/m/' . $slug,
        'display'          => 'standalone',
        'theme_color'      => $appJson['theme']['primary'] ?? '#F5A623',
        'background_color' => $appJson['theme']['bg']      ?? '#FFFFFF',
        'icons'            => $appJson['icons'] ?? [],
    ])->withHeader('Content-Type', 'application/manifest+json');
});

$router->get('/m/{slug}/sw.js', function ($req, $slug) {
    $js = "self.addEventListener('install', e => self.skipWaiting());\n"
        . "self.addEventListener('fetch', e => {});";
    return (new Response($js))->withHeader('Content-Type', 'application/javascript');
});

// ─── i18n: resolver locale + carregar strings ───
function mobile_cfg(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $f = framework_root() . '/config/mobile.php';
        $cfg = is_file($f) ? (require $f) : [];
        if (!is_array($cfg)) {
            $cfg = [];
        }
    }
    return $cfg;
}

function mobile_locale(): string
{
    $cfg = mobile_cfg();
    $supported = $cfg['supported_locales'] ?? ['en'];
    $default   = $cfg['default_locale'] ?? ($supported[0] ?? 'en');

    // 1. Query ?lang=xx
    $q = (string) ($_GET['lang'] ?? '');
    if ($q !== '' && in_array($q, $supported, true)) {
        return $q;
    }

    // 2. Cookie
    $c = (string) ($_COOKIE['beaver_locale'] ?? '');
    if ($c !== '' && in_array($c, $supported, true)) {
        return $c;
    }

    // 3. Header Accept-Language (primeira tag que bate)
    $h = (string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
    if ($h !== '') {
        foreach (preg_split('/\s*,\s*/', $h) as $tag) {
            $tag = strtolower(substr($tag, 0, 5));
            foreach ($supported as $s) {
                if ($tag === $s || str_starts_with($tag, $s . '-')) {
                    return $s;
                }
            }
        }
    }
    return $default;
}

function mobile_i18n(string $slug, string $locale): array
{
    $appBase = mobile_find_app_dir($slug) ?: (mobile_projects_dir() . '/' . $slug);
    $file = $appBase . '/locales/' . $locale . '.json';
    if (is_file($file)) {
        $d = json_decode((string) file_get_contents($file), true);
        if (is_array($d)) {
            return $d;
        }
    }
    return [];
}

// ─── Helper: renderiza um screen mobile ───
// Aceita $productId opcional que injecta window.__PRODUCT__ no HTML.
function mobile_render_screen(string $slug, string $screen, ?string $productId = null)
{
    $slug   = (string) $slug;
    $screen = (string) $screen;

    if (!preg_match('/^[a-z][a-z0-9_-]*$/', $slug)) {
        return Response::text('Slug inválido', 400);
    }
    if (!preg_match('/^[a-z][a-z0-9_-]*$/', $screen)) {
        return Response::text('Tela inválida', 400);
    }

    $projectPath = mobile_find_app_dir($slug);
    if ($projectPath === null) {
        return Response::html('<h1>Projeto não encontrado</h1><p>' . htmlspecialchars($slug) . '</p>', 404);
    }

    $screenFile = $projectPath . '/screens/' . $screen . '.json';
    if (!is_file($screenFile)) {
        return Response::html('<h1>Tela não encontrada</h1><p>' . htmlspecialchars($slug . '/' . $screen) . '</p>', 404);
    }

    $appJson    = is_file($projectPath . '/app.json')
        ? json_decode((string) file_get_contents($projectPath . '/app.json'), true) ?: []
        : [];
    $screenData = json_decode((string) file_get_contents($screenFile), true) ?: [];

    $title   = $screenData['title'] ?? $screen;
    $appName = $appJson['name'] ?? $slug;
    $json    = json_encode($screenData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // ─── Ler API_KEY do .env do projeto mobile ───
    $apiKey = '';
    $envFile = $projectPath . '/.env';
    if (is_file($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            if (str_starts_with($line, 'API_KEY=')) {
                $apiKey = trim(substr($line, 8), " \t\"'");
                break;
            }
        }
    }

    // ─── Recolher CSS/JS custom dos smart components presentes na tela ───
    $customCssTags = '';
    $customJsTags  = '';
    $customFlags   = [];

    $compTypes = [];
    foreach ($screenData['components'] ?? [] as $c) {
        if (!empty($c['type'])) {
            $compTypes[$c['type']] = true;
        }
    }

    $smartBuildDir = framework_root() . '/build/smart/' . $slug;
    foreach (array_keys($compTypes) as $ct) {
        $ctSafe = preg_replace('/[^a-z0-9_-]/', '', (string) $ct);
        if ($ctSafe === '') {
            continue;
        }

        $cssFile = $smartBuildDir . '/' . $ctSafe . '.css';
        if (is_file($cssFile)) {
            $v = filemtime($cssFile);
            $customCssTags .= '<link rel="stylesheet" href="/smart/' . $slug . '/' . $ctSafe . '.css?v=' . $v . '">';
        }

        $jsFile = $smartBuildDir . '/' . $ctSafe . '.js';
        if (is_file($jsFile)) {
            $v = filemtime($jsFile);
            $customJsTags .= '<script src="/smart/' . $slug . '/' . $ctSafe . '.js?v=' . $v . '"></script>';
            $customFlags[$ctSafe] = true;
        }
    }

    // ─── Injectar productId no HTML (so na rota /m/p/{pid}/{screen}) ───
    $productScript = '';
    if ($productId !== null && $productId !== '') {
        $productScript = '<script>window.__PRODUCT__=' . json_encode(['id' => $productId], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';</script>';
    }

    // ─── Injectar locale + traducoes ───
    $locale = mobile_locale();
    $i18n   = mobile_i18n($slug, $locale);
    $localePayload = [
        'locale'    => $locale,
        'supported' => array_values(mobile_cfg()['supported_locales'] ?? [$locale]),
        'i18n'      => empty($i18n) ? new \stdClass() : $i18n,
    ];
    $localeScript = '<script>window.__I18N__=' . json_encode($localePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';</script>';
    $slugScript   = '<script>window.__APP_SLUG__=' . json_encode($slug, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';</script>';
    // Nota: JSON_FORCE_OBJECT garante que 'i18n' e '{}' (nao '[]') quando vazio

    $html = '<!DOCTYPE html><html lang="' . htmlspecialchars($locale) . '"><head>'
        . '<meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">'
        . '<title>' . htmlspecialchars($title) . ' · ' . htmlspecialchars($appName) . '</title>'
        . $productScript . $localeScript . $slugScript . $customCssTags
        . '<style>'
        . '*{box-sizing:border-box;margin:0;padding:0}'
        . 'body{font-family:-apple-system,BlinkMacSystemFont,"Inter",sans-serif;background:#F4F5F7;color:#2B2F36;min-height:100vh}'
        . '.app{max-width:480px;margin:0 auto;background:#fff;min-height:100vh;padding:20px;padding-top:42px}'
        . '.comp{margin-bottom:12px}'
        . '.appbar{display:flex;justify-content:space-between;align-items:center;padding:10px 0;font-weight:800;font-size:1.05rem;border-bottom:1px solid #E4E6EA;margin-bottom:12px}'
        . '.search{width:100%;padding:10px 16px;border-radius:20px;border:1px solid #E4E6EA;background:#F4F5F7;font-size:.9rem;font-family:inherit}'
        . '.btn{display:block;width:100%;padding:14px;border-radius:12px;text-align:center;font-weight:800;cursor:pointer;border:none;font-family:inherit;font-size:.95rem}'
        . '.btn.primary{background:linear-gradient(150deg,#FFC46B,#E07800);color:#fff;box-shadow:0 4px 12px rgba(224,120,0,.3)}'
        . '.btn.secondary{background:#fff;color:#2B2F36;border:1.5px solid #E4E6EA}'
        . '.slide{position:relative;aspect-ratio:4/3;background:#eee;border-radius:14px;background-size:cover;background-position:center;overflow:hidden;margin-bottom:12px;box-shadow:0 4px 16px rgba(0,0,0,.1)}'
        . '.slide-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.75) 0%,transparent 55%)}'
        . '.slide-content{position:absolute;left:20px;right:20px;bottom:20px;color:#fff;z-index:2}'
        . '.slide-title{font-size:1.4rem;font-weight:800;margin-bottom:4px;text-shadow:0 2px 6px rgba(0,0,0,.5)}'
        . '.slide-desc{font-size:.9rem;opacity:.9;margin-bottom:14px}'
        . '.slide-buy{display:inline-block;background:linear-gradient(150deg,#FFC46B,#E07800);color:#fff;padding:12px 28px;border-radius:26px;font-weight:800;font-size:.95rem;border:none;cursor:pointer;font-family:inherit;box-shadow:0 6px 20px rgba(224,120,0,.5)}'
        . '.h{font-size:1.1rem;font-weight:800;margin:20px 0 10px}'
        . '.chip{display:inline-block;padding:8px 16px;background:#F4F5F7;border-radius:18px;margin:4px 4px 4px 0;font-size:.85rem;font-weight:600}'
        . '.list{background:#FAFBFC;border-radius:10px;padding:14px;text-align:center;color:#8A9099;font-size:.85rem;font-style:italic}'
        . '.text{color:#5F656D;font-size:.92rem;line-height:1.5}'
        . '.placeholder{padding:14px;background:#F4F5F7;border:1.5px dashed #C9CDD3;border-radius:8px;text-align:center;font-size:.78rem;color:#8A9099}'
        . '.dots{display:flex;gap:6px;justify-content:center;margin-top:-30px;margin-bottom:20px;position:relative;z-index:5}'
        . '.dot{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.4);border:none;cursor:pointer;padding:0}'
        . '.dot.active{background:#F5A623;width:24px;border-radius:4px}'
        . '</style></head><body>'
        . '<div class="app" id="app"></div>'
        . '<script>window.__API_KEY__=' . json_encode($apiKey) . ';</script>'
        . '<script>window.__SCREEN__=' . $json . ';</script>'
        . ($customFlags ? '<script>window.__smartCustom=' . json_encode($customFlags) . ';</script>' : '')
        . '<script src="/mobile-editor/js/render-screen.js?v='
        . filemtime(framework_root() . '/plugins/mobile-editor/resources/js/render-screen.js')
        . '"></script>'
        . $customJsTags
        . '</body></html>';

    return Response::html($html)->withHeader("Cache-Control", "no-store, no-cache, must-revalidate, max-age=0")->withHeader("Pragma", "no-cache")->withHeader("Expires", "0");
}

// ─── Rota publica: catalogo de produtos (BD first, ficheiro fallback) ───
// Aceita products.json (EN) e produtos.json (PT)
function mobile_serve_products(string $slug, string $source = 'auto')
{
    $source = in_array($source, ['auto', 'db', 'json'], true) ? $source : 'auto';

    // 1. Tentar BD (se source=auto ou db)
    if ($source === 'auto' || $source === 'db') {
        try {
            $pdo = \Beaver\Plugins\MobileEditor\AppDatabase::forApp($slug);
            if ($pdo) {
                $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name IN ('products','produtos') LIMIT 1")->fetchAll();
                if (!empty($tables)) {
                    $tn = $tables[0]['name'];
                    $rows = $pdo->query('SELECT * FROM `' . $tn . '` ORDER BY id')->fetchAll();
                    // Decodificar JSON de imagens
                    foreach ($rows as &$r) {
                        if (isset($r['imagens']) && is_string($r['imagens'])) {
                            $dec = json_decode($r['imagens'], true);
                            if (is_array($dec)) {
                                $r['imagens'] = $dec;
                            }
                        }
                    }
                    if (!empty($rows)) {
                        return Response::text(json_encode(['products' => $rows], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
                            ->withHeader('Content-Type', 'application/json; charset=UTF-8')
                            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
                    }
                }
            }
        } catch (\Throwable $e) {
            /* fallback */
        }
    }

    // 2. Ficheiro (se source=auto ou json, OU fallback se db falhou)
    if ($source === 'auto' || $source === 'json') {
        $dir = mobile_find_app_dir($slug) ?: (mobile_projects_dir() . '/' . $slug);
        foreach (['products.json', 'produtos.json'] as $f) {
            if (is_file($dir . '/' . $f)) {
                $raw = (string) file_get_contents($dir . '/' . $f);
                $dec = json_decode($raw, true);
                // Se veio do ficheiro com chave "produtos", normalizar para "products"
                if (is_array($dec) && isset($dec['produtos']) && !isset($dec['products'])) {
                    $dec['products'] = $dec['produtos'];
                    unset($dec['produtos']);
                    $raw = json_encode($dec, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                return Response::text($raw)
                    ->withHeader('Content-Type', 'application/json; charset=UTF-8')
                    ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
            }
        }
    }

    return Response::json(['ok' => false, 'error' => 'products not found (source=' . $source . ')'], 404);
}

$router->get('/m/{slug}/products.json', function ($req, $slug) {
    $slug = (string) $slug;
    if (!preg_match('/^[a-z][a-z0-9_-]*$/', $slug)) {
        return Response::json(['ok' => false, 'error' => 'invalid slug'], 400);
    }
    $source = (string) ($_GET['source'] ?? 'auto');
    return mobile_serve_products($slug, $source);
});

$router->get('/m/{slug}/produtos.json', function ($req, $slug) {
    $slug = (string) $slug;
    if (!preg_match('/^[a-z][a-z0-9_-]*$/', $slug)) {
        return Response::json(['ok' => false, 'error' => 'invalid slug'], 400);
    }
    $source = (string) ($_GET['source'] ?? 'auto');
    return mobile_serve_products($slug, $source);
});

$router->get('/m/{slug}/{screen}', function ($req, $slug, $screen) {
    return mobile_render_screen((string) $slug, (string) $screen, null);
});

// ─── Rota de PRODUTO: /m/p/{pid}/{screen} ───
// O slug e resolvido por config/mobile.php (fallback: MOBILE_DEFAULT_SLUG env, ou 'a-minha-app').
$router->get('/m/p/{pid}/{screen}', function ($req, $pid, $screen) {
    $pid    = preg_replace('/[^a-z0-9_-]/i', '', (string) $pid);
    $screen = (string) $screen;

    if ($pid === '' || !preg_match('/^[a-z][a-z0-9_-]*$/', $screen)) {
        return Response::text('URL invalida', 400);
    }

    $slug = null;
    $cfgFile = framework_root() . '/config/mobile.php';
    if (is_file($cfgFile)) {
        $cfg = require $cfgFile;
        if (is_array($cfg)) {
            if (isset($cfg['products'][$pid])) {
                $slug = preg_replace('/[^a-z0-9_-]/', '', (string) $cfg['products'][$pid]);
            } elseif (isset($cfg['default_slug'])) {
                $slug = preg_replace('/[^a-z0-9_-]/', '', (string) $cfg['default_slug']);
            }
        }
    }
    if (!$slug) {
        $slug = getenv('MOBILE_DEFAULT_SLUG') ?: 'a-minha-app';
        $slug = preg_replace('/[^a-z0-9_-]/', '', (string) $slug);
    }

    return mobile_render_screen($slug, $screen, $pid);
});


// ── Slug sem screen → vai para start_screen do app.json ──
$router->get('/m/{slug}', function ($req, $slug) {
    $slug = (string) $slug;
    if (!preg_match('/^[a-z][a-z0-9_-]*$/', $slug)) {
        return Response::text('Slug inválido', 400);
    }

    $appBase = mobile_find_app_dir($slug) ?: (mobile_projects_dir() . '/' . $slug);
    $appFile = $appBase . '/app.json';
    $start   = 'dashboard';
    if (is_file($appFile)) {
        $app = json_decode((string) file_get_contents($appFile), true) ?: [];
        if (!empty($app['start_screen'])) {
            $start = $app['start_screen'];
        }
    }
    return Response::redirect('/m/' . $slug . '/' . $start);
});

// ============================================================
// LOJA BEAVER
// ============================================================
use App\Controllers\LojaController;

$router->get('/loja', fn() => (new LojaController())->index());
$router->get('/loja/produto', fn() => (new LojaController())->produto());
$router->get('/loja/carrinho', fn() => (new LojaController())->carrinho());
$router->post('/loja/carrinho', fn() => (new LojaController())->carrinho());
$router->get('/loja/checkout', fn() => (new LojaController())->checkoutForm());
$router->post('/loja/checkout', fn() => (new LojaController())->checkoutPost());
$router->get('/loja/sucesso', fn() => (new LojaController())->sucesso());
$router->get('/loja/api/cart', fn() => (new LojaController())->apiCart());
$router->post('/loja/api/cart', fn() => (new LojaController())->apiCart());



$router->get('/features', function () {
    $view = dirname(__DIR__) . '/app/views/features.php';
    if (!is_file($view)) return Response::text('Features indisponível', 404);
    ob_start();
    require $view;
    return Response::html(ob_get_clean());
});
// ═════════════════════════════════════════════════════════════
// DOCUMENTAÇÃO
// ═════════════════════════════════════════════════════════════

$router->get('/docs', function () {
    $view = dirname(__DIR__) . '/app/views/docs/index.php';
    if (!is_file($view)) return Response::text('Documentação indisponível', 404);
    ob_start();
    require $view;
    return Response::html(ob_get_clean());
});

$router->get('/manual', function () {
    $view = dirname(__DIR__) . '/app/views/docs/manual.php';
    if (!is_file($view)) return Response::text('Manual indisponível', 404);
    ob_start();
    require $view;
    return Response::html(ob_get_clean());
});

$router->get('/sdk', function () {
    $view = dirname(__DIR__) . '/app/views/docs/sdk.php';
    if (!is_file($view)) return Response::text('SDK indisponível', 404);
    ob_start();
    require $view;
    return Response::html(ob_get_clean());
});




// ═════════════════════════════════════════════════════════════
// SITE OFICIAL — AUTENTICAÇÃO E PAINEL
// ═════════════════════════════════════════════════════════════


// ── Autenticação (/auth/*) ──
$router->get('/auth/login',    fn() => (new AuthController())->showLogin());
$router->post('/auth/login',    fn() => (new AuthController())->login());
$router->post('/auth/perfil',   fn() => (new AuthController())->atualizarPerfil());
$router->get('/auth/registar', fn() => (new AuthController())->showRegistar());
$router->post('/auth/registar', fn() => (new AuthController())->registar());
$router->post('/auth/logout',   fn() => (new AuthController())->logout());

// ── Painel do utilizador (/painel/*) ──
$router->get('/painel',               fn() => (new PainelController())->index());
$router->get('/painel/perfil',        fn() => (new PainelController())->perfil());
$router->post('/painel/perfil',        fn() => (new PainelController())->perfilGuardar());
$router->get('/painel/download/{id}', fn($req, $id) => (new PainelController())->download((string) $id));

// ═════════════════════════════════════════════════════════════
// FÓRUM — COMUNIDADE
// ═════════════════════════════════════════════════════════════


$router->get('/forum',                    fn() => (new ForumController())->index());
$router->get('/forum/novo',               fn() => (new ForumController())->novoForm());
$router->post('/forum/novo',               fn() => (new ForumController())->criar());
$router->get('/forum/t/{slug}',           fn($req, $slug) => (new ForumController())->thread((string) $slug));
$router->get('/forum/t/{slug}/editar',    fn($req, $slug) => (new ForumController())->editarForm((string) $slug));
$router->post('/forum/t/{slug}/editar',    fn($req, $slug) => (new ForumController())->editar((string) $slug));
$router->post('/forum/t/{slug}/apagar',    fn($req, $slug) => (new ForumController())->apagar((string) $slug));
$router->post('/forum/t/{slug}/responder', fn($req, $slug) => (new ForumController())->responder((string) $slug));
$router->post('/forum/t/{slug}/fixar',     fn($req, $slug) => (new ForumController())->fixar((string) $slug));
$router->post('/forum/t/{slug}/fechar',    fn($req, $slug) => (new ForumController())->fechar((string) $slug));

// ═════════════════════════════════════════════════════════════
// API v1
// ═════════════════════════════════════════════════════════════
require __DIR__ . '/api.php';

// Beaver API — landing
$router->get('/beaver-api', function () {
    $viewPath = __DIR__ . '/../app/views/beaver-api.php';
    if (!is_file($viewPath)) {
        return \Beaver\Http\Response::text('Beaver API — em construção', 200);
    }
    ob_start();
    require $viewPath;
    return \Beaver\Http\Response::html(ob_get_clean(), 200, [
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
    ]);
});

// Documentação — Beaver API
$router->get('/docs/beaver-api', function () {
    $viewPath = __DIR__ . '/../app/views/docs/beaver-api.php';
    if (!is_file($viewPath)) {
        return \Beaver\Http\Response::text('Documentação — em construção', 200);
    }
    ob_start();
    require $viewPath;
    return \Beaver\Http\Response::html(ob_get_clean(), 200, [
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
    ]);
});

// Job Angel — landing
$router->get('/job-angel', function () {
    $viewPath = __DIR__ . '/../app/views/job-angel.php';
    if (!is_file($viewPath)) {
        return \Beaver\Http\Response::text('Job Angel — em construção', 200);
    }
    ob_start();
    require $viewPath;
    return \Beaver\Http\Response::html(ob_get_clean(), 200, [
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
    ]);
});
