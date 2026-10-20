<?php

declare(strict_types=1);

use Beaver\Http\Response;
use Beaver\Plugins\SqlWizardLite\Http\AssetHelper;

// UI principal
$router->get('/sqlwizard-lite', function () use ($plugin) {
    // ═══════════════════════════════════════════════════════
    // Utilizador — tentar 6 fontes, por ordem
    // ═══════════════════════════════════════════════════════
    $user = null;
    $log = function ($msg) {
        @file_put_contents('/tmp/sqlwizard-debug.log', date('c') . ' ' . $msg . PHP_EOL, FILE_APPEND);
    };

    // 1) Forçar session_start
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    $log('session_start: ' . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE'));
    $log('session_id: ' . (session_id() ?: '(vazio)'));
    $log('$_SESSION keys: ' . (empty($_SESSION) ? '(vazio)' : implode(',', array_keys($_SESSION))));
    $log('$_COOKIE keys: ' . (empty($_COOKIE) ? '(vazio)' : implode(',', array_keys($_COOKIE))));

    // 2) Forçar driver do AuthManager
    if (class_exists(\Beaver\Auth\AuthManager::class)) {
        try {
            \Beaver\Auth\AuthManager::driver();
            $log('AuthManager::driver() OK');
        } catch (\Throwable $e) {
            $log('AuthManager::driver() erro: ' . $e->getMessage());
        }
    } else {
        $log('AuthManager NAO existe');
    }

    // 3) Auth::user()
    if (class_exists(\Beaver\Auth\Auth::class)) {
        try {
            $u = \Beaver\Auth\Auth::user();
            if ($u) {
                if (is_object($u)) {
                    if (method_exists($u, 'toArray')) $user = $u->toArray();
                    elseif (method_exists($u, 'getAttributes')) $user = $u->getAttributes();
                    else $user = json_decode(json_encode($u), true) ?: [];
                } elseif (is_array($u)) {
                    $user = $u;
                }
                $log('Auth::user(): ' . ($user ? 'name=' . ($user['name'] ?? $user['nome'] ?? '?') : 'NULL'));
            } else {
                $log('Auth::user(): NULL');
            }
        } catch (\Throwable $e) {
            $log('Auth::user() erro: ' . $e->getMessage());
        }
    }

    // 4) $_SESSION['user_id']
    if (!$user && !empty($_SESSION['user_id'])) {
        try {
            $row = \App\Services\SiteDB::one("SELECT * FROM users WHERE id = :id", [":id" => (int) $_SESSION['user_id']]);
            if ($row) { $user = $row; $log('via $_SESSION[user_id]: name=' . ($user['nome'] ?? '?')); }
        } catch (\Throwable $e) { $log('$_SESSION[user_id] erro: ' . $e->getMessage()); }
    }

    // 5) cookie auth-token
    if (!$user && !empty($_COOKIE['auth-token'])) {
        try {
            $row = \App\Services\SiteDB::one(
                "SELECT u.* FROM users u INNER JOIN user_sessions s ON s.user_id = u.id WHERE s.id = :t AND s.expira_em > :now",
                [":t" => (string) $_COOKIE['auth-token'], ":now" => time()]
            );
            if ($row) { $user = $row; $log('via auth-token: name=' . ($user['nome'] ?? '?')); }
        } catch (\Throwable $e) { $log('auth-token erro: ' . $e->getMessage()); }
    }

    // 6) UserService::atual()
    if (!$user && class_exists(\App\Services\UserService::class)) {
        try {
            $u = \App\Services\UserService::atual();
            if (is_array($u)) { $user = $u; $log('via UserService: name=' . ($user['nome'] ?? '?')); }
        } catch (\Throwable $e) {}
    }

    // 7) Fallback: procurar por qualquer user (debug — remover depois)
    if (!$user) {
        $log('TODAS as fontes falharam — vou tentar ler SESSION e COOKIE crus');
        if (!empty($_SESSION)) {
            $log('SESSION: ' . json_encode($_SESSION));
        }
        if (!empty($_COOKIE)) {
            $log('COOKIE: ' . json_encode($_COOKIE));
        }
    }

    // Normalizar
    if (is_array($user)) {
        if (empty($user['name']) && !empty($user['nome'])) $user['name'] = $user['nome'];
        if (empty($user['nome']) && !empty($user['name'])) $user['nome'] = $user['name'];

        $role = strtolower((string)($user['role'] ?? 'user'));
        $trialUsed = !empty($user['trial_used']);
        $trialExpires = $user['trial_expires_at'] ?? null;

        if (in_array($role, ['admin', 'pro', 'premium', 'paid'], true)) {
            $user['plan'] = 'pro';
            $user['plan_expires_at'] = null;
        } elseif ($trialUsed && $trialExpires) {
            $user['plan'] = 'trial';
            $user['plan_expires_at'] = date('c', (int) $trialExpires);
        } else {
            $user['plan'] = 'free';
            $user['plan_expires_at'] = null;
        }

        $log('FINAL: name=' . ($user['name'] ?? '?') . ' plan=' . $user['plan'] . ' email=' . ($user['email'] ?? '?'));
    } else {
        $log('FINAL: user = NULL');
    }

    // CSRF
    $csrfToken = '';
    foreach (['csrf_token', '_csrf', 'csrf', '_token'] as $k) {
        if (!empty($_SESSION[$k])) { $csrfToken = (string) $_SESSION[$k]; break; }
    }

    ob_start();
    require $plugin->path . '/resources/views/index.php';
    $html = ob_get_clean();

    return \Beaver\Http\Response::html($html, 200, [
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        'Pragma'        => 'no-cache',
        'Expires'       => '0',
    ]);
});



// Página de checkout (pagamento)
// Página Business Angel
$router->get('/business-angel', function () use ($plugin) {
    $user = null;
    if (session_status() === PHP_SESSION_NONE) { @session_start(); }
    if (class_exists(\Beaver\Auth\AuthManager::class)) {
        try { \Beaver\Auth\AuthManager::driver(); } catch (\Throwable $e) {}
    }
    if (class_exists(\Beaver\Auth\Auth::class)) {
        try {
            $u = \Beaver\Auth\Auth::user();
            if ($u) {
                if (is_object($u)) {
                    $user = method_exists($u, 'toArray') ? $u->toArray() : (array) $u;
                } elseif (is_array($u)) {
                    $user = $u;
                }
            }
        } catch (\Throwable $e) {}
    }
    if (is_array($user)) {
        if (empty($user['name']) && !empty($user['nome'])) $user['name'] = $user['nome'];
    } else {
        $user = ['name' => 'Visitante', 'email' => '', 'plan' => 'free'];
    }
    ob_start();
    require $plugin->path . '/resources/views/business-angel.php';
    return \Beaver\Http\Response::html(ob_get_clean(), 200, [
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
    ]);
});

// ─── Helper interno do checkout (partilhado entre rotas) ───
$renderCheckout = function ($product, $price, $planLabel, $planPeriod) use ($plugin) {
    // ═══════════════════════════════════════════════════════
    // TABELA DE PREÇOS (fonte da verdade)
    // ═══════════════════════════════════════════════════════
    $prices = [
        'pro'       => 9.00,
        'business'  => 29.00,
        'angel_10'  => 10.00,
        'angel_50'  => 50.00,
        'angel_200' => 200.00,
    ];

    // Valores por omissão
    if ($product === null || $product === '') $product = 'pro';
    $product = strtolower((string) $product);

    // Validar produto
    if (!isset($prices[$product])) {
        return \Beaver\Http\Response::text('Produto inválido: ' . htmlspecialchars($product), 404);
    }

    // Validar/forçar preço correto
    $expectedPrice = (float) $prices[$product];
    $givenPrice = ($price === null || $price === '') ? $expectedPrice : (float) $price;

    if (abs($givenPrice - $expectedPrice) > 0.01) {
        // Preço adulterado → redirecionar para o preço correto
        $correctUrl = '/checkout/' . urlencode($product) . '/' . number_format($expectedPrice, 2, '.', '');
        if (!headers_sent()) {
            header('Location: ' . $correctUrl, true, 302);
            exit;
        }
    }

    // Usar o preço correto (não o do URL)
    $price = number_format($expectedPrice, 2, '.', '');
    if ($planLabel === null || $planLabel === '') $planLabel = ucfirst($product);
    if ($planPeriod === null || $planPeriod === '') $planPeriod = 'mês';

    // Preencher $_GET para o checkout.php usar
    $_GET['product'] = $product;
    $_GET['price'] = $price;
    $_GET['plan_label'] = $planLabel;
    $_GET['plan_period'] = $planPeriod;

$user = null;
    if (session_status() === PHP_SESSION_NONE) { @session_start(); }
    if (class_exists(\Beaver\Auth\AuthManager::class)) {
        try { \Beaver\Auth\AuthManager::driver(); } catch (\Throwable $e) {}
    }
    if (class_exists(\Beaver\Auth\Auth::class)) {
        try {
            $u = \Beaver\Auth\Auth::user();
            if ($u) {
                if (is_object($u)) {
                    $user = method_exists($u, 'toArray') ? $u->toArray() : (array) $u;
                } elseif (is_array($u)) {
                    $user = $u;
                }
            }
        } catch (\Throwable $e) {}
    }
    if (is_array($user)) {
        if (empty($user['name']) && !empty($user['nome'])) $user['name'] = $user['nome'];
    } else {
        $user = ['name' => 'Visitante', 'email' => '', 'plan' => 'free'];
    }
    $activePage = 'checkout';
    ob_start();
    require $plugin->path . '/resources/views/checkout.php';
    return \Beaver\Http\Response::html(ob_get_clean(), 200, [
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        'Pragma'        => 'no-cache',
    ]);
};

// ─── Rota antiga (query string) — retrocompatibilidade ───
$router->get('/checkout', function () use ($plugin, $renderCheckout) {
    return $renderCheckout(
        $_GET['product'] ?? 'pro',
        $_GET['price'] ?? '9',
        $_GET['plan_label'] ?? null,
        $_GET['plan_period'] ?? null
    );
});

// ─── Rota limpa: /checkout/{product}/{price} ───
$router->get('/checkout/{product}/{price}', function ($req, $product, $price) use ($plugin, $renderCheckout) {
    return $renderCheckout(
        (string) $product,
        (string) $price,
        null,
        null
    );
});

// ─── Rota limpa com período: /checkout/{product}/{price}/{period} ───
$router->get('/checkout/{product}/{price}/{period}', function ($req, $product, $price, $period) use ($plugin, $renderCheckout) {
    return $renderCheckout(
        (string) $product,
        (string) $price,
        null,
        (string) $period
    );
});

// Página de planos (upgrade)
$router->get('/painel/upgrade', function () use ($plugin) {
    // Obter user
    $user = null;
    if (session_status() === PHP_SESSION_NONE) { @session_start(); }
    if (class_exists(\Beaver\Auth\AuthManager::class)) {
        try { \Beaver\Auth\AuthManager::driver(); } catch (\Throwable $e) {}
    }
    if (class_exists(\Beaver\Auth\Auth::class)) {
        try {
            $u = \Beaver\Auth\Auth::user();
            if ($u) {
                if (is_object($u)) {
                    $user = method_exists($u, 'toArray') ? $u->toArray() : (array) $u;
                } elseif (is_array($u)) {
                    $user = $u;
                }
            }
        } catch (\Throwable $e) {}
    }
    // Normalizar nome → name
    if (is_array($user)) {
        if (empty($user['name']) && !empty($user['nome'])) $user['name'] = $user['nome'];
        // Plano
        if (empty($user['plan'])) {
            $role = strtolower((string)($user['role'] ?? 'user'));
            if (in_array($role, ['admin', 'pro', 'premium'], true)) {
                $user['plan'] = 'pro';
            } else {
                $user['plan'] = 'free';
            }
        }
    } else {
        $user = ['name' => 'Visitante', 'email' => '', 'plan' => 'free'];
    }

    $activePage = 'upgrade';
    ob_start();
    require $plugin->path . '/resources/views/upgrade.php';
    return \Beaver\Http\Response::html(ob_get_clean(), 200, [
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        'Pragma'        => 'no-cache',
    ]);
});

// Assets — mesmo padrão do full: /plugins/{slug}/...
$router->get('/plugins/sqlwizard-lite/css/{file}', function ($req, $file) use ($plugin) {
    return AssetHelper::serve($plugin->path, 'css', (string) $file);
});

$router->get('/plugins/sqlwizard-lite/js/{file}', function ($req, $file) use ($plugin) {
    return AssetHelper::serve($plugin->path, 'js', (string) $file);
});

$router->get('/plugins/sqlwizard-lite/img/{file}', function ($req, $file) use ($plugin) {
    return AssetHelper::serve($plugin->path, 'img', (string) $file);
});

// Templates JSON
$router->get('/plugins/sqlwizard-lite/templates/{file}', function ($req, $file) use ($plugin) {
    $path = $plugin->path . '/resources/ui/templates/' . basename((string) $file);
    if (!is_file($path)) {
        return \Beaver\Http\Response::text('Template não encontrado', 404);
    }
    return new \Beaver\Http\Response(
        (string) file_get_contents($path),
        200,
        ['Content-Type' => 'application/json; charset=UTF-8']
    );
});
