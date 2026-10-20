<?php

/**
 * sdk.php — Beaver Framework
 * SDK oficial: instalação, cliente, autenticação, pedidos, respostas e erros.
 */

declare(strict_types=1);

$SDK_SECTIONS = [
  [
    'id'    => 'install',
    'name'  => 'Instalação',
    'icon'  => 'terminal',
    'tags'  => ['setup', 'composer', 'core'],
    'desc'  => 'Instala o SDK via Composer e liga-o ao teu projeto em segundos.',
    'component' => <<<'PHP'
// composer.json
{
    "require": {
        "beaver/sdk": "^1.0"
    }
}
PHP,
    'instance' => <<<'BASH'
composer require beaver/sdk

# ou a partir do repositório
composer require onidesk-ti/beaver-sdk
BASH,
    'example' => <<<'BASH'
✔ Instalado beaver/sdk (v1.0.0)
✔ Autoload regenerado
✔ Pronto a usar

# Verificar
php -r "require 'vendor/autoload.php'; var_dump(class_exists('Beaver\\Sdk\\Client'));"
# bool(true)
BASH,
  ],
  [
    'id'    => 'client',
    'name'  => 'Client',
    'icon'  => 'code',
    'tags'  => ['setup', 'core'],
    'desc'  => 'Cliente principal do SDK. Configura base URL, headers e timeout num único objeto.',
    'component' => <<<'PHP'
namespace Beaver\Sdk;

class Client
{
    public function __construct(
        public readonly string $baseUrl,
        public readonly string $apiKey,
        public readonly int $timeout = 30,
        public readonly array $headers = [],
    ) {}

    public function get(string $path, array $query = []): Response;
    public function post(string $path, array $body = []): Response;
    public function put(string $path, array $body = []): Response;
    public function delete(string $path): Response;
}
PHP,
    'instance' => <<<'PHP'
use Beaver\Sdk\Client;

$client = new Client(
    baseUrl: 'https://api.beaver.dev',
    apiKey:  getenv('BEAVER_API_KEY'),
    timeout: 15,
    headers: [
        'Accept-Language' => 'pt-PT',
        'X-App'           => 'meu-projeto',
    ],
);
PHP,
    'example' => <<<'PHP'
$response = $client->get('/v1/products', [
    'page'    => 1,
    'per_page' => 20,
]);

if ($response->ok()) {
    foreach ($response->json('data') as $product) {
        echo $product['name'], PHP_EOL;
    }
}
PHP,
  ],
  [
    'id'    => 'auth',
    'name'  => 'Autenticação',
    'icon'  => 'key',
    'tags'  => ['auth', 'segurança'],
    'desc'  => 'Chave de API, Bearer Token ou OAuth2. O SDK renova tokens automaticamente.',
    'component' => <<<'PHP'
namespace Beaver\Sdk\Auth;

interface Authenticator
{
    public function authorize(Request $request): Request;
    public function refresh(): void;
    public function expiresAt(): ?int;
}
PHP,
    'instance' => <<<'PHP'
use Beaver\Sdk\Auth\BearerToken;

$auth = new BearerToken(
    token:        $accessToken,
    refreshToken: $refreshToken,
    expiresIn:    3600,
);

$client = new Client(
    baseUrl: 'https://api.beaver.dev',
    auth:    $auth,
);
PHP,
    'example' => <<<'PHP'
// API key
$client = new Client(
    baseUrl: 'https://api.beaver.dev',
    apiKey:  'bv_live_xxxxxxxxxxxx',
);

// OAuth2
$auth = new OAuth2(
    clientId:     'app_123',
    clientSecret: 'sh_xxxx',
    redirectUri:  'https://meu-app.pt/callback',
);
PHP,
  ],
  [
    'id'    => 'request',
    'name'  => 'Request',
    'icon'  => 'route',
    'tags'  => ['http', 'core'],
    'desc'  => 'Objeto imutável que representa um pedido HTTP. Suporta query, body e headers.',
    'component' => <<<'PHP'
namespace Beaver\Sdk;

class Request
{
    public string $method;
    public string $path;
    public array  $query    = [];
    public array  $body     = [];
    public array  $headers  = [];

    public function withHeader(string $key, string $value): self;
    public function withQuery(array $query): self;
    public function withBody(array $body): self;
}
PHP,
    'instance' => <<<'PHP'
use Beaver\Sdk\Request;

$req = new Request('POST', '/v1/products');
$req = $req
    ->withHeader('X-Idempotency-Key', bin2hex(random_bytes(16)))
    ->withQuery(['dry_run' => 1])
    ->withBody([
        'name'  => 'Café',
        'price' => 2.50,
    ]);

$client->send($req);
PHP,
    'example' => <<<'PHP'
$req = (new Request('PATCH', '/v1/products/42'))
    ->withBody(['price' => 3.20]);

$response = $client->send($req);

if ($response->status() === 200) {
    echo 'Atualizado em ', $response->header('X-Elapsed'), ' ms';
}
PHP,
  ],
  [
    'id'    => 'response',
    'name'  => 'Response',
    'icon'  => 'check',
    'tags'  => ['http', 'core'],
    'desc'  => 'Wrapper à volta da resposta HTTP. Acesso a status, headers e JSON deserializado.',
    'component' => <<<'PHP'
namespace Beaver\Sdk;

class Response
{
    public function status(): int;
    public function ok(): bool;
    public function header(string $key, ?string $default = null): ?string;
    public function body(): string;
    public function json(string $key = null, mixed $default = null): mixed;
    public function isPaginated(): bool;
}
PHP,
    'instance' => <<<'PHP'
$response = $client->get('/v1/products');

if ($response->ok()) {
    $items = $response->json('data');
    $total = $response->json('meta.total');
    echo count($items), ' de ', $total, ' produtos';
}
PHP,
    'example' => <<<'PHP'
// Resposta paginada
$response = $client->get('/v1/products', ['per_page' => 50]);

foreach ($response->json('data') as $p) {
    echo $p['name'], PHP_EOL;
}

if ($response->isPaginated()) {
    $next = $response->json('links.next');
    // usar $next como cursor
}
PHP,
  ],
  [
    'id'    => 'errors',
    'name'  => 'Erros',
    'icon'  => 'shield',
    'tags'  => ['segurança', 'core'],
    'desc'  => 'Hierarquia de exceções específicas por código HTTP. Nada de "if status === 404".',
    'component' => <<<'PHP'
namespace Beaver\Sdk\Exception;

class BeaverException extends \RuntimeException {}
class ApiException    extends BeaverException {}
class ValidationError extends ApiException {}
class NotFound        extends ApiException {}
class Unauthorized    extends ApiException {}
class RateLimited     extends ApiException {}
class ServerError     extends ApiException {}
PHP,
    'instance' => <<<'PHP'
use Beaver\Sdk\Exception\NotFound;
use Beaver\Sdk\Exception\Unauthorized;

try {
    $client->get('/v1/products/99999');
} catch (NotFound $e) {
    echo 'Produto não existe: ', $e->getMessage();
} catch (Unauthorized $e) {
    // renovar token
}
PHP,
    'example' => <<<'PHP'
try {
    $client->post('/v1/products', $data);
} catch (ValidationError $e) {
    return response()->json([
        'errors' => $e->errors(),   // ['name' => ['obrigatório']]
    ], 422);
} catch (RateLimited $e) {
    sleep($e->retryAfter());
    // retry
}
PHP,
  ],
  [
    'id'    => 'pagination',
    'name'  => 'Paginação',
    'icon'  => 'layers',
    'tags'  => ['http', 'performance'],
    'desc'  => 'Iteração automática por todas as páginas com generators — sem loops manuais.',
    'component' => <<<'PHP'
namespace Beaver\Sdk;

class Paginator implements \IteratorAggregate
{
    public function __construct(
        private Client $client,
        private string $path,
        private array  $query = [],
        private int    $perPage = 50,
    ) {}

    public function getIterator(): \Generator;
    public function all(): array;
}
PHP,
    'instance' => <<<'PHP'
use Beaver\Sdk\Paginator;

$pages = new Paginator($client, '/v1/products', ['active' => 1]);

foreach ($pages as $product) {
    echo $product['name'], PHP_EOL;
}

// Ou carregar tudo em memória
$all = $pages->all();
PHP,
    'example' => <<<'PHP'
// Sync incremental com cursor
$paginator = new Paginator($client, '/v1/orders', [
    'since' => '2024-01-01',
], perPage: 100);

foreach ($paginator as $order) {
    if ($order['status'] === 'paid') {
        sendReceipt($order);
    }
}
PHP,
  ],
  [
    'id'    => 'webhooks',
    'name'  => 'Webhooks',
    'icon'  => 'bell',
    'tags'  => ['http', 'segurança'],
    'desc'  => 'Recebe eventos do Beaver, valida a assinatura HMAC e reencaminha para handlers.',
    'component' => <<<'PHP'
namespace Beaver\Sdk\Webhook;

class Handler
{
    public function __construct(
        private string $secret,
    ) {}

    public function verify(string $payload, string $signature): bool;
    public function on(string $event, callable $handler): void;
    public function dispatch(string $payload): void;
}
PHP,
    'instance' => <<<'PHP'
use Beaver\Sdk\Webhook\Handler;

$handler = new Handler(secret: getenv('BEAVER_WEBHOOK_SECRET'));

$handler->on('order.paid', function ($event) {
    sendReceipt($event['data']);
});

$handler->on('user.created', function ($event) {
    sendWelcomeEmail($event['data']['email']);
});
PHP,
    'example' => <<<'PHP'
// webhooks.php
$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_BEAVER_SIGNATURE'] ?? '';

if (!$handler->verify($payload, $signature)) {
    http_response_code(401);
    exit('Invalid signature');
}

$handler->dispatch($payload);
http_response_code(200);
PHP,
  ],
];

/* Paginação */
$perPage   = 6;
$page      = max(1, (int)($_GET['page'] ?? 1));
$total     = count($SDK_SECTIONS);
$pages     = max(1, (int)ceil($total / $perPage));
$page      = min($page, $pages);
$offset    = ($page - 1) * $perPage;
$pageItems = array_slice($SDK_SECTIONS, $offset, $perPage);
?>
<!DOCTYPE html>
<html lang="pt">
<header>
  <div class="nav-inner">
    <a class="brand" href="/">
      <span class="brand-logo">🦫</span>
      <span>
        <span class="brand-name">Beaver<span class="accent">.</span></span>
        <span class="brand-sub">Framework</span>
      </span>
    </a>
    <nav class="nav-links">
      <a class="nav-link" href="/features">💎 Features</a>

      <div class="has-dropdown" id="dropDocs">
        <a href="#" class="nav-link" id="dropDocsToggle">📚 Documentação <span class="chev">▾</span></a>
        <div class="dropdown" role="menu">
          <a href="/docs" class="drop-item"><span class="drop-ico amber">📖</span><span><strong>Documentação</strong><small>Guia completo</small></span></a>
          <a href="/exemplos" class="drop-item"><span class="drop-ico green">💡</span><span><strong>Exemplos</strong><small>Código pronto</small></span></a>
        </div>
      </div>

      <a class="nav-link active" href="/sdk">🧪 SDK</a>
      <a class="nav-link" href="/plugins">🧩 Plugins</a>
      <a class="nav-link" href="/marketplace">🏪 Marketplace</a>

      <div class="has-dropdown" id="dropProd">
        <a href="#" class="nav-link" id="dropToggle">📦 Produtos <span class="chev">▾</span></a>
        <div class="dropdown" role="menu">
          <a href="/app-sqlwizard" class="drop-item"><span class="drop-ico amber">🗄️</span><span><strong>SqlWizard</strong><small>Editor SQL</small></span></a>
          <a href="/frankei-trial" class="drop-item"><span class="drop-ico violet">🔍</span><span><strong>Frankei</strong><small>Testes</small></span></a>
        </div>
      </div>

      <a class="nav-link" href="/loja">🛒 Loja</a>

      <div class="has-dropdown settings-dropdown" id="settingsPicker">
        <a href="#" class="nav-link settings-btn" id="settingsToggle">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="3" />
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
          </svg>
        </a>
        <div class="dropdown settings-menu" role="menu">
          <div class="settings-section">
            <div class="settings-label">Tema</div>
            <div class="settings-options">
              <button type="button" data-theme-value="amber" class="settings-option"><span class="swatch amber"></span><span class="nm">Âmbar</span><span class="chk">✓</span></button>
              <button type="button" data-theme-value="ocean" class="settings-option"><span class="swatch ocean"></span><span class="nm">Oceano</span><span class="chk">✓</span></button>
              <button type="button" data-theme-value="forest" class="settings-option"><span class="swatch forest"></span><span class="nm">Floresta</span><span class="chk">✓</span></button>
            </div>
          </div>
          <div class="settings-divider"></div>
          <div class="settings-section">
            <div class="settings-label">Idioma</div>
            <div class="settings-options">
              <a href="?lang=pt" class="settings-option active"><span class="flag">🇵🇹</span><span class="nm">Português</span><span class="chk">✓</span></a>
              <a href="?lang=es" class="settings-option"><span class="flag">🇪🇸</span><span class="nm">Español</span><span class="chk">✓</span></a>
              <a href="?lang=en" class="settings-option"><span class="flag">🇬🇧</span><span class="nm">English</span><span class="chk">✓</span></a>
              <a href="?lang=fr" class="settings-option"><span class="flag">🇫🇷</span><span class="nm">Français</span><span class="chk">✓</span></a>
              <a href="?lang=de" class="settings-option"><span class="flag">🇩🇪</span><span class="nm">Deutsch</span><span class="chk">✓</span></a>
              <a href="?lang=it" class="settings-option"><span class="flag">🇮🇹</span><span class="nm">Italiano</span><span class="chk">✓</span></a>
            </div>
          </div>
        </div>
      </div>

      <a class="nav-cta" href="https://github.com/Onidesk-TI/beaver-framework" target="_blank" rel="noopener">🐙 GitHub →</a>
    </nav>
  </div>
</header>
</head>

<body>

  <svg width="0" height="0" style="position:absolute" aria-hidden="true">
    <defs>
      <linearGradient id="bvg" x1="0" y1="0" x2="1" y2="1">
        <stop offset="0%" stop-color="#FFE7BE" />
        <stop offset="48%" stop-color="#F5A623" />
        <stop offset="100%" stop-color="#E07800" />
      </linearGradient>
      <symbol id="beaver" viewBox="0 0 64 64">
        <circle cx="13" cy="17.5" r="7.5" fill="url(#bvg)" />
        <circle cx="51" cy="17.5" r="7.5" fill="url(#bvg)" />
        <ellipse cx="32" cy="33" rx="23" ry="21" fill="url(#bvg)" />
        <ellipse cx="32" cy="41" rx="15.5" ry="12" fill="#8A4A00" opacity=".28" />
        <ellipse cx="32" cy="34.5" rx="4.6" ry="3.2" fill="#2B1606" />
        <circle cx="22.5" cy="26" r="3.4" fill="#2B1606" />
        <circle cx="41.5" cy="26" r="3.4" fill="#2B1606" />
        <circle cx="23.6" cy="25" r="1.2" fill="#FFFCF6" />
        <circle cx="42.6" cy="25" r="1.2" fill="#FFFCF6" />
        <rect x="27.3" y="44.5" width="4.3" height="10.5" rx="1.7" fill="#FFFCF6" />
        <rect x="32.4" y="44.5" width="4.3" height="10.5" rx="1.7" fill="#FFFCF6" />
      </symbol>

      <symbol id="ico-home" viewBox="0 0 24 24">
        <path d="M3 11l9-8 9 8" />
        <path d="M5 10v10h14V10" />
        <path d="M9 20v-6h6v6" />
      </symbol>
      <symbol id="ico-book" viewBox="0 0 24 24">
        <path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H19a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H6.5A2.5 2.5 0 0 0 4 22.5V4.5z" />
        <path d="M4 4.5A2.5 2.5 0 0 0 6.5 7H20" />
      </symbol>
      <symbol id="ico-gh" viewBox="0 0 24 24">
        <path d="M12 .5C5.7.5.5 5.7.5 12c0 5.1 3.3 9.4 7.9 10.9.6.1.8-.2.8-.6v-2c-3.2.7-3.9-1.5-3.9-1.5-.5-1.3-1.3-1.7-1.3-1.7-1.1-.7.1-.7.1-.7 1.2.1 1.8 1.2 1.8 1.2 1 1.8 2.7 1.3 3.4 1 .1-.8.4-1.3.7-1.6-2.6-.3-5.3-1.3-5.3-5.8 0-1.3.5-2.3 1.2-3.1-.1-.3-.5-1.5.1-3.1 0 0 1-.3 3.3 1.2a11.5 11.5 0 016 0C17.6 4.7 18.6 5 18.6 5c.6 1.6.2 2.8.1 3.1.8.8 1.2 1.8 1.2 3.1 0 4.5-2.7 5.5-5.3 5.8.4.4.8 1.1.8 2.2v3.3c0 .3.2.7.8.6 4.6-1.5 7.9-5.8 7.9-10.9C23.5 5.7 18.3.5 12 .5z" />
      </symbol>
      <symbol id="ico-search" viewBox="0 0 24 24">
        <circle cx="11" cy="11" r="7" />
        <path d="M21 21l-4.3-4.3" />
      </symbol>
      <symbol id="ico-route" viewBox="0 0 24 24">
        <circle cx="6" cy="19" r="2.5" />
        <circle cx="18" cy="5" r="2.5" />
        <path d="M8.5 19h5a4 4 0 0 0 0-8h-3a4 4 0 0 1 0-8" />
      </symbol>
      <symbol id="ico-code" viewBox="0 0 24 24">
        <path d="M9 18l-6-6 6-6" />
        <path d="M15 6l6 6-6 6" />
      </symbol>
      <symbol id="ico-layers" viewBox="0 0 24 24">
        <path d="M12 3l9 5-9 5-9-5 9-5z" />
        <path d="M3 13l9 5 9-5" />
      </symbol>
      <symbol id="ico-shield" viewBox="0 0 24 24">
        <path d="M12 2l9 4v6c0 5-3.6 8.7-9 10-5.4-1.3-9-5-9-10V6l9-4z" />
      </symbol>
      <symbol id="ico-check" viewBox="0 0 24 24">
        <path d="M5 13l4 4 10-10" />
      </symbol>
      <symbol id="ico-key" viewBox="0 0 24 24">
        <circle cx="8" cy="15" r="4" />
        <path d="M11 12l9-9 3 3-3 3 2 2-3 3-2-2-3 3" />
      </symbol>
      <symbol id="ico-bell" viewBox="0 0 24 24">
        <path d="M6 16V11a6 6 0 0 1 12 0v5l2 2H4l2-2z" />
        <path d="M10 20a2 2 0 0 0 4 0" />
      </symbol>
      <symbol id="ico-terminal" viewBox="0 0 24 24">
        <rect x="3" y="4" width="18" height="16" rx="2" />
        <path d="M7 9l3 3-3 3" />
        <path d="M13 15h4" />
      </symbol>
      <symbol id="ico-copy" viewBox="0 0 24 24">
        <rect x="9" y="9" width="12" height="12" rx="2" />
        <path d="M5 15V5a2 2 0 0 1 2-2h10" />
      </symbol>
      <symbol id="ico-chev-l" viewBox="0 0 24 24">
        <path d="M15 6l-6 6 6 6" />
      </symbol>
      <symbol id="ico-chev-r" viewBox="0 0 24 24">
        <path d="M9 6l6 6-6 6" />
      </symbol>
    </defs>
  </svg>

  <header>
    <div class="nav-inner">
      <a href="/" class="brand" aria-label="Beaver Framework — início">
        <span class="brand-logo"><svg aria-hidden="true">
            <use href="#beaver" />
          </svg></span>
        <span class="brand-text">
          <span class="brand-name">Beaver<span class="accent">.</span></span>
          <span class="brand-sub">Framework</span>
        </span>
      </a>

      <nav class="nav-links" aria-label="Navegação principal">
        <a class="nav-link" href="/">
          <svg aria-hidden="true">
            <use href="#ico-home" />
          </svg>
          <span>Home</span>
        </a>
        <a class="nav-link" href="/docs">
          <svg aria-hidden="true">
            <use href="#ico-book" />
          </svg>
          <span>Documentação</span>
        </a>
        <a class="nav-link active" href="/sdk.php" aria-current="page">
          <svg aria-hidden="true">
            <use href="#ico-terminal" />
          </svg>
          <span>SDK</span>
        </a>
        <a class="nav-cta" href="https://github.com/Frank-Onidesk/beaver-framework" target="_blank" rel="noopener noreferrer">
          <svg aria-hidden="true">
            <use href="#ico-gh" />
          </svg>
          GitHub
        </a>
      </nav>
    </div>
  </header>

  <section class="hero">
    <h1>
      SDK
      <span class="badge">v1.0 · <?= date('Y') ?></span>
    </h1>
    <p>
      SDK oficial do Beaver Framework. Instalação, cliente, autenticação, pedidos,
      respostas, paginação, webhooks e tratamento de erros — tudo numa API coerente
      e testada.
    </p>
  </section>

  <div class="filters-wrap">
    <div class="filters" role="search">
      <label class="search">
        <svg aria-hidden="true">
          <use href="#ico-search" />
        </svg>
        <input
          id="q"
          type="search"
          placeholder="Pesquisar no SDK…"
          autocomplete="off"
          aria-label="Pesquisar no SDK">
      </label>

      <div class="filter-tags" id="tags" role="group" aria-label="Filtrar por categoria">
        <button class="tag-btn active" data-tag="all" type="button">Tudo</button>
        <button class="tag-btn" data-tag="setup" type="button">Setup</button>
        <button class="tag-btn" data-tag="core" type="button">Core</button>
        <button class="tag-btn" data-tag="http" type="button">HTTP</button>
        <button class="tag-btn" data-tag="auth" type="button">Auth</button>
        <button class="tag-btn" data-tag="segurança" type="button">Segurança</button>
        <button class="tag-btn" data-tag="performance" type="button">Performance</button>
      </div>

      <span class="filter-count" id="count"><b><?= $total ?></b> secções</span>
    </div>
  </div>

  <section class="list" id="list">
    <?php foreach ($pageItems as $i => $c): ?>
      <article
        class="card"
        data-id="<?= htmlspecialchars($c['id']) ?>"
        data-tags="<?= htmlspecialchars(implode(' ', $c['tags'])) ?>"
        data-name="<?= htmlspecialchars($c['name']) ?>"
        data-desc="<?= htmlspecialchars($c['desc']) ?>"
        style="animation-delay:<?= $i * 40 ?>ms">
        <header class="card-head">
          <span class="card-ico" aria-hidden="true">
            <svg>
              <use href="#ico-<?= htmlspecialchars($c['icon']) ?>" />
            </svg>
          </span>
          <div class="card-info">
            <div class="card-title-row">
              <h2 class="card-title"><?= htmlspecialchars($c['name']) ?></h2>
              <span class="card-id"><?= htmlspecialchars($c['id']) ?></span>
            </div>
            <p class="card-desc"><?= htmlspecialchars($c['desc']) ?></p>
            <div class="card-tags">
              <?php foreach ($c['tags'] as $t): ?>
                <span class="t"><?= htmlspecialchars($t) ?></span>
              <?php endforeach; ?>
            </div>
          </div>
        </header>

        <div class="tabs" role="tablist" aria-label="Vistas de <?= htmlspecialchars($c['name']) ?>">
          <button class="tab active" role="tab" aria-selected="true" data-pane="component" type="button">
            <svg aria-hidden="true">
              <use href="#ico-code" />
            </svg>
            Definição
          </button>
          <button class="tab" role="tab" aria-selected="false" data-pane="instance" type="button">
            <svg aria-hidden="true">
              <use href="#ico-layers" />
            </svg>
            Instância
          </button>
          <button class="tab" role="tab" aria-selected="false" data-pane="example" type="button">
            <svg aria-hidden="true">
              <use href="#ico-check" />
            </svg>
            Exemplo
          </button>
        </div>

        <div class="panes">
          <div class="pane active" data-pane="component" role="tabpanel">
            <div class="code-block">
              <div class="code-head">
                <span class="code-lang"><i></i> Definição</span>
                <button class="copy-btn" type="button" data-copy>
                  <svg aria-hidden="true">
                    <use href="#ico-copy" />
                  </svg> copiar
                </button>
              </div>
              <pre><?= htmlspecialchars(trim($c['component'])) ?></pre>
            </div>
          </div>

          <div class="pane" data-pane="instance" role="tabpanel">
            <div class="code-block">
              <div class="code-head">
                <span class="code-lang"><i></i> Instância</span>
                <button class="copy-btn" type="button" data-copy>
                  <svg aria-hidden="true">
                    <use href="#ico-copy" />
                  </svg> copiar
                </button>
              </div>
              <pre><?= htmlspecialchars(trim($c['instance'])) ?></pre>
            </div>
          </div>

          <div class="pane" data-pane="example" role="tabpanel">
            <div class="code-block">
              <div class="code-head">
                <span class="code-lang"><i></i> Exemplo</span>
                <button class="copy-btn" type="button" data-copy>
                  <svg aria-hidden="true">
                    <use href="#ico-copy" />
                  </svg> copiar
                </button>
              </div>
              <pre><?= htmlspecialchars(trim($c['example'])) ?></pre>
            </div>
          </div>
        </div>
      </article>
    <?php endforeach; ?>

    <div class="empty" id="empty">
      <div class="ico"><svg aria-hidden="true">
          <use href="#ico-search" />
        </svg></div>
      <strong>Sem resultados</strong>
      <span>Tenta outro termo ou remove os filtros ativos.</span>
    </div>
  </section>

  <?php if ($pages > 1): ?>
    <nav class="pagination" aria-label="Paginação">
      <a class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>"
        href="?page=<?= max(1, $page - 1) ?>"
        aria-label="Página anterior"
        <?= $page <= 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
        <svg aria-hidden="true">
          <use href="#ico-chev-l" />
        </svg>
      </a>

      <?php for ($p = 1; $p <= $pages; $p++): ?>
        <a class="page-num <?= $p === $page ? 'active' : '' ?>"
          href="?page=<?= $p ?>"
          <?= $p === $page ? 'aria-current="page"' : '' ?>>
          <?= $p ?>
        </a>
      <?php endfor; ?>

      <a class="page-btn <?= $page >= $pages ? 'disabled' : '' ?>"
        href="?page=<?= min($pages, $page + 1) ?>"
        aria-label="Página seguinte"
        <?= $page >= $pages ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
        <svg aria-hidden="true">
          <use href="#ico-chev-r" />
        </svg>
      </a>
    </nav>
  <?php endif; ?>

  <footer>
    © <span id="ano"></span> Beaver Framework · Feito com <span class="accent">🦫</span> em Portugal
  </footer>

  <script>
    document.querySelectorAll('.card').forEach(card => {
      const tabs = card.querySelectorAll('.tab');
      const panes = card.querySelectorAll('.pane');

      tabs.forEach(tab => {
        tab.addEventListener('click', () => {
          const target = tab.dataset.pane;
          tabs.forEach(t => {
            const on = t === tab;
            t.classList.toggle('active', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
          });
          panes.forEach(p => {
            p.classList.toggle('active', p.dataset.pane === target);
          });
        });
      });
    });

    document.querySelectorAll('[data-copy]').forEach(btn => {
      btn.addEventListener('click', async () => {
        const pre = btn.closest('.code-block').querySelector('pre');
        try {
          await navigator.clipboard.writeText(pre.textContent);
          const old = btn.innerHTML;
          btn.classList.add('ok');
          btn.innerHTML = '<svg aria-hidden="true"><use href="#ico-check"/></svg> copiado';
          setTimeout(() => {
            btn.classList.remove('ok');
            btn.innerHTML = old;
          }, 1400);
        } catch {}
      });
    });

    const q = document.getElementById('q');
    const tagsWrap = document.getElementById('tags');
    const list = document.getElementById('list');
    const empty = document.getElementById('empty');
    const count = document.getElementById('count');
    const cards = Array.from(document.querySelectorAll('.card'));

    let activeTag = 'all';

    function normalize(str) {
      return (str || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    function applyFilters() {
      const term = normalize(q.value.trim());
      let visible = 0;

      cards.forEach(card => {
        const haystack = normalize(
          card.dataset.name + ' ' + card.dataset.desc + ' ' + card.dataset.tags + ' ' + card.dataset.id
        );
        const tags = card.dataset.tags.split(/\s+/);

        const matchTerm = !term || haystack.includes(term);
        const matchTag = activeTag === 'all' || tags.includes(activeTag);
        const show = matchTerm && matchTag;

        card.style.display = show ? '' : 'none';
        if (show) visible++;
      });

      empty.classList.toggle('show', visible === 0);
      count.innerHTML = `<b>${visible}</b> secç${visible === 1 ? 'ão' : 'ões'}`;
    }

    q.addEventListener('input', applyFilters);

    tagsWrap.addEventListener('click', e => {
      const btn = e.target.closest('.tag-btn');
      if (!btn) return;
      tagsWrap.querySelectorAll('.tag-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      activeTag = btn.dataset.tag;
      applyFilters();
    });

    document.getElementById('ano').textContent = new Date().getFullYear();
  </script>
</body>

</html>