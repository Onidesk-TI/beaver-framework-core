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
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SDK — Beaver Framework 🦫</title>
<meta name="description" content="SDK oficial do Beaver Framework: instalação, cliente, autenticação, pedidos, respostas e erros.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<style>
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  :root{
    --amber:#F5A623;
    --amber-2:#E07800;
    --amber-3:#FFC46B;
    --amber-4:#FFE0A6;
    --amber-deep:#8A4A00;

    --gray-50:#FAFAFA;
    --gray-100:#F4F5F7;
    --gray-200:#EAECEF;
    --gray-300:#D9DCE1;
    --gray-400:#B8BDC4;
    --gray-500:#8A9099;
    --gray-600:#5F656D;
    --gray-700:#3F444B;

    --bg:#F4F5F7;
    --card:#FFFFFF;
    --text:#2B2F36;
    --text-soft:#5F656D;
    --muted:#8A9099;

    --line:#E4E6EA;
    --line-strong:#D2D6DC;

    --code-bg:#FAF7F0;
    --code-text:#5A3A10;

    --ok:#2E9B5C;
  }
  html,body{height:100%}
  body{
    font-family:'Inter',system-ui,-apple-system,sans-serif;
    background:var(--bg);
    color:var(--text);
    line-height:1.65;
    -webkit-font-smoothing:antialiased;
    overflow-x:hidden;
    min-height:100vh;
    display:flex;
    flex-direction:column;
  }
  body::before{
    content:"";position:fixed;inset:0;z-index:-2;
    background:
      radial-gradient(900px 560px at 10% -5%, rgba(245,166,35,.22), transparent 62%),
      radial-gradient(800px 500px at 92% 6%, rgba(255,196,107,.30), transparent 60%),
      radial-gradient(900px 560px at 50% 110%, rgba(224,120,0,.10), transparent 65%),
      linear-gradient(180deg, #FBFBFC 0%, var(--bg) 60%, #EEF0F3 100%);
  }
  body::after{
    content:"";position:fixed;inset:0;z-index:-1;pointer-events:none;
    background-image:
      linear-gradient(rgba(120,90,40,.05) 1px,transparent 1px),
      linear-gradient(90deg,rgba(120,90,40,.05) 1px,transparent 1px);
    background-size:56px 56px;
    mask-image:radial-gradient(ellipse 100% 60% at 50% 0%,#000 25%,transparent 82%);
    -webkit-mask-image:radial-gradient(ellipse 100% 60% at 50% 0%,#000 25%,transparent 82%);
  }
  ::selection{background:rgba(245,166,35,.45);color:#3A2400}

  header{
    position:sticky;top:0;z-index:50;
    backdrop-filter:blur(20px) saturate(170%);
    -webkit-backdrop-filter:blur(20px) saturate(170%);
    background:rgba(250,250,252,.82);
    border-bottom:1px solid var(--line);
  }
  .nav-inner{
    max-width:1240px;margin:0 auto;padding:0 24px;
    display:flex;align-items:center;justify-content:space-between;gap:20px;
    height:72px;
  }
  .brand{display:flex;align-items:center;gap:11px;text-decoration:none;color:inherit;min-width:0}
  .brand-logo{
    width:42px;height:42px;border-radius:13px;flex:none;
    background:linear-gradient(150deg,var(--amber-4),var(--amber-3) 55%,var(--amber));
    border:1.5px solid rgba(138,74,0,.35);
    display:grid;place-items:center;
    box-shadow:0 8px 20px -10px rgba(224,120,0,.6), inset 0 1px 0 rgba(255,255,255,.7);
    transition:transform .3s;
  }
  .brand:hover .brand-logo{transform:rotate(-8deg) scale(1.06)}
  .brand-logo svg{width:28px;height:28px}
  .brand-text{display:flex;flex-direction:column}
  .brand-name{font-weight:800;font-size:1.2rem;letter-spacing:-.03em;line-height:1.1;color:#2B2F36}
  .brand-name .accent{color:var(--amber-2)}
  .brand-sub{
    font-size:.6rem;font-weight:700;letter-spacing:.22em;
    color:var(--amber-deep);text-transform:uppercase;line-height:1.2;
  }

  .nav-links{display:flex;align-items:center;gap:6px;flex:none}
  .nav-link{
    display:inline-flex;align-items:center;gap:8px;
    padding:9px 14px;
    border-radius:10px;
    font-size:.86rem;font-weight:600;
    color:var(--gray-600);
    text-decoration:none;
    transition:all .2s;
    border:1px solid transparent;
  }
  .nav-link:hover{
    color:var(--amber-deep);
    background:rgba(245,166,35,.10);
    border-color:rgba(224,120,0,.22);
  }
  .nav-link.active{
    color:var(--amber-deep);
    background:rgba(245,166,35,.14);
    border-color:rgba(224,120,0,.30);
  }
  .nav-link svg{width:15px;height:15px;flex:none;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}

  .nav-cta{
    display:inline-flex;align-items:center;gap:8px;
    padding:10px 16px;
    border-radius:100px;
    font-size:.84rem;font-weight:700;
    color:#FFFFFF;
    text-decoration:none;
    background:linear-gradient(150deg,var(--amber-3),var(--amber) 50%,var(--amber-2));
    border:1px solid rgba(138,74,0,.35);
    box-shadow:0 8px 20px -10px rgba(224,120,0,.85), inset 0 1px 0 rgba(255,255,255,.55);
    text-shadow:0 1px 2px rgba(120,60,0,.30);
    transition:all .22s;
  }
  .nav-cta:hover{
    transform:translateY(-1px);
    box-shadow:0 14px 26px -12px rgba(224,120,0,1), inset 0 1px 0 rgba(255,255,255,.6);
  }
  .nav-cta svg{width:15px;height:15px;stroke:#fff;fill:none;stroke-width:2.2;stroke-linecap:round;stroke-linejoin:round}

  .hero{
    max-width:1240px;margin:0 auto;padding:44px 24px 8px;
    width:100%;
  }
  .hero h1{
    font-size:clamp(1.6rem,3vw,2.2rem);
    letter-spacing:-.035em;
    font-weight:800;
    color:#2B2F36;
    margin-bottom:8px;
    display:flex;align-items:center;gap:12px;
    flex-wrap:wrap;
  }
  .hero h1 .badge{
    font-family:'JetBrains Mono',monospace;
    font-size:.7rem;font-weight:700;
    padding:5px 10px;border-radius:100px;
    background:rgba(245,166,35,.18);
    color:var(--amber-deep);
    border:1px solid rgba(224,120,0,.28);
    letter-spacing:.02em;
    text-transform:uppercase;
  }
  .hero p{
    color:var(--text-soft);
    max-width:720px;
    font-size:.98rem;
  }

  .filters-wrap{
    max-width:1240px;margin:0 auto;padding:20px 24px 0;
    width:100%;
    position:sticky;top:72px;z-index:40;
  }
  .filters{
    background:rgba(255,255,255,.92);
    backdrop-filter:blur(16px) saturate(150%);
    -webkit-backdrop-filter:blur(16px) saturate(150%);
    border:1px solid var(--line);
    border-radius:16px;
    padding:12px;
    display:flex;align-items:center;gap:10px;
    box-shadow:0 12px 30px -18px rgba(60,60,70,.35), inset 0 1px 0 rgba(255,255,255,.9);
    flex-wrap:wrap;
  }
  .search{
    flex:1;min-width:220px;
    display:flex;align-items:center;gap:10px;
    padding:0 14px;
    background:var(--gray-50);
    border:1.5px solid var(--gray-200);
    border-radius:11px;
    transition:all .2s;
    height:44px;
  }
  .search:focus-within{
    border-color:var(--amber);
    background:#fff;
    box-shadow:0 0 0 4px rgba(245,166,35,.18);
  }
  .search svg{
    width:17px;height:17px;flex:none;
    stroke:var(--gray-500);fill:none;stroke-width:2.2;
    stroke-linecap:round;stroke-linejoin:round;
  }
  .search input{
    border:0;outline:0;background:transparent;
    font-family:inherit;
    font-size:.92rem;color:var(--text);
    width:100%;
    font-weight:500;
  }
  .search input::placeholder{color:var(--gray-400)}

  .filter-tags{display:flex;gap:6px;flex-wrap:wrap}
  .tag-btn{
    border:1.5px solid var(--gray-200);
    background:#fff;
    color:var(--gray-600);
    padding:8px 13px;
    border-radius:100px;
    font-size:.78rem;
    font-weight:700;
    cursor:pointer;
    transition:all .2s;
    font-family:inherit;
    letter-spacing:.01em;
  }
  .tag-btn:hover{
    border-color:var(--amber-3);
    color:var(--amber-deep);
    background:rgba(245,166,35,.06);
  }
  .tag-btn.active{
    background:linear-gradient(150deg,var(--amber-3),var(--amber));
    border-color:rgba(138,74,0,.35);
    color:#fff;
    text-shadow:0 1px 2px rgba(120,60,0,.3);
    box-shadow:0 6px 14px -8px rgba(224,120,0,.85);
  }

  .filter-count{
    font-family:'JetBrains Mono',monospace;
    font-size:.75rem;
    color:var(--muted);
    font-weight:600;
    padding:0 8px;
    white-space:nowrap;
  }
  .filter-count b{color:var(--amber-2);font-weight:800}

  .list{
    max-width:1240px;margin:0 auto;padding:24px 24px 32px;
    width:100%;
    display:flex;flex-direction:column;gap:16px;
  }

  .card{
    background:var(--card);
    border:1px solid var(--line);
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 10px 24px -18px rgba(60,60,70,.35);
    transition:border-color .2s, box-shadow .2s, transform .2s;
    animation:cardIn .35s ease both;
  }
  @keyframes cardIn{
    from{opacity:0;transform:translateY(8px)}
    to{opacity:1;transform:none}
  }
  .card:hover{
    border-color:rgba(224,120,0,.32);
    box-shadow:0 16px 34px -22px rgba(224,120,0,.55);
  }

  .card-head{
    display:flex;align-items:flex-start;gap:16px;
    padding:20px 22px;
    border-bottom:1px solid var(--line);
    background:linear-gradient(180deg,#FFFFFF, #FDFAF3);
  }
  .card-ico{
    width:48px;height:48px;border-radius:12px;flex:none;
    display:grid;place-items:center;
    background:linear-gradient(140deg,var(--amber-4),var(--amber-3));
    border:1px solid rgba(138,74,0,.28);
    box-shadow:inset 0 1px 0 rgba(255,255,255,.7);
  }
  .card-ico svg{
    width:24px;height:24px;
    stroke:var(--amber-deep);fill:none;
    stroke-width:1.9;stroke-linecap:round;stroke-linejoin:round;
  }

  .card-info{flex:1;min-width:0}
  .card-title-row{
    display:flex;align-items:center;gap:10px;flex-wrap:wrap;
    margin-bottom:4px;
  }
  .card-title{
    font-size:1.12rem;
    font-weight:800;
    letter-spacing:-.02em;
    color:#2B2F36;
  }
  .card-id{
    font-family:'JetBrains Mono',monospace;
    font-size:.7rem;
    color:var(--amber-deep);
    background:rgba(245,166,35,.16);
    padding:3px 8px;
    border-radius:6px;
    border:1px solid rgba(224,120,0,.22);
    font-weight:600;
  }
  .card-desc{
    color:var(--text-soft);
    font-size:.9rem;
  }
  .card-tags{
    display:flex;gap:6px;flex-wrap:wrap;
    margin-top:10px;
  }
  .card-tags .t{
    font-family:'JetBrains Mono',monospace;
    font-size:.68rem;
    font-weight:600;
    color:var(--gray-600);
    background:var(--gray-100);
    border:1px solid var(--gray-200);
    padding:2px 8px;
    border-radius:6px;
    letter-spacing:.01em;
  }

  .tabs{
    display:flex;
    background:#FBF9F4;
    border-bottom:1px solid var(--line);
    padding:0 12px;
    gap:2px;
    overflow-x:auto;
    scrollbar-width:none;
  }
  .tabs::-webkit-scrollbar{display:none}
  .tab{
    position:relative;
    border:0;
    background:transparent;
    font-family:inherit;
    font-size:.83rem;
    font-weight:700;
    color:var(--gray-500);
    padding:12px 16px;
    cursor:pointer;
    letter-spacing:.01em;
    transition:color .18s;
    white-space:nowrap;
    display:inline-flex;
    align-items:center;
    gap:7px;
  }
  .tab:hover{color:var(--amber-deep)}
  .tab.active{color:var(--amber-2)}
  .tab.active::after{
    content:"";
    position:absolute;left:12px;right:12px;bottom:-1px;height:2.5px;
    background:linear-gradient(90deg,var(--amber-3),var(--amber-2));
    border-radius:3px 3px 0 0;
  }
  .tab svg{width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:2.2;stroke-linecap:round;stroke-linejoin:round}

  .panes{
    background:var(--code-bg);
    position:relative;
    overflow:hidden;
  }
  .pane{
    display:none;
    padding:18px 22px 20px;
    animation:paneIn .25s ease both;
  }
  .pane.active{display:block}
  @keyframes paneIn{
    from{opacity:0;transform:translateY(3px)}
    to{opacity:1;transform:none}
  }

  .code-block{
    position:relative;
    background:#FFFFFF;
    border:1px solid var(--line-strong);
    border-radius:12px;
    overflow:hidden;
  }
  .code-head{
    display:flex;align-items:center;justify-content:space-between;
    padding:8px 12px;
    background:linear-gradient(180deg,#FBF9F4,#F6F3EC);
    border-bottom:1px solid var(--line);
    font-family:'JetBrains Mono',monospace;
    font-size:.68rem;
    font-weight:700;
    color:var(--amber-deep);
    letter-spacing:.06em;
    text-transform:uppercase;
  }
  .code-lang{
    display:inline-flex;align-items:center;gap:7px;
  }
  .code-lang i{
    width:7px;height:7px;border-radius:50%;
    background:var(--amber);display:block;
    box-shadow:0 0 8px rgba(245,166,35,.9);
  }
  .copy-btn{
    border:1px solid var(--line-strong);
    background:#fff;
    color:var(--gray-600);
    font-family:inherit;
    font-size:.68rem;
    font-weight:700;
    padding:4px 9px;
    border-radius:6px;
    cursor:pointer;
    letter-spacing:.04em;
    transition:all .18s;
    display:inline-flex;align-items:center;gap:5px;
  }
  .copy-btn:hover{
    color:var(--amber-deep);
    border-color:rgba(224,120,0,.4);
    background:rgba(245,166,35,.08);
  }
  .copy-btn.ok{
    color:#fff;
    background:var(--ok);
    border-color:var(--ok);
  }
  .copy-btn svg{width:11px;height:11px;stroke:currentColor;fill:none;stroke-width:2.4;stroke-linecap:round;stroke-linejoin:round}

  pre{
    margin:0;
    padding:14px 16px;
    overflow-x:auto;
    font-family:'JetBrains Mono',monospace;
    font-size:.78rem;
    line-height:1.7;
    color:var(--code-text);
    background:#FFFFFF;
    font-weight:500;
  }
  pre::-webkit-scrollbar{height:8px}
  pre::-webkit-scrollbar-thumb{background:var(--gray-300);border-radius:4px}

  .pagination{
    max-width:1240px;margin:8px auto 40px;
    padding:0 24px;
    width:100%;
    display:flex;justify-content:center;
    align-items:center;
    gap:6px;
    flex-wrap:wrap;
  }
  .page-btn,
  .page-num{
    min-width:40px;
    height:40px;
    padding:0 12px;
    border-radius:10px;
    border:1.5px solid var(--line-strong);
    background:#fff;
    color:var(--gray-600);
    font-family:inherit;
    font-size:.85rem;
    font-weight:700;
    cursor:pointer;
    text-decoration:none;
    display:inline-flex;align-items:center;justify-content:center;gap:6px;
    transition:all .18s;
  }
  .page-btn:hover:not(:disabled),
  .page-num:hover{
    color:var(--amber-deep);
    border-color:rgba(224,120,0,.45);
    background:rgba(245,166,35,.08);
  }
  .page-num.active{
    background:linear-gradient(150deg,var(--amber-3),var(--amber));
    border-color:rgba(138,74,0,.35);
    color:#fff;
    text-shadow:0 1px 2px rgba(120,60,0,.3);
    box-shadow:0 8px 18px -10px rgba(224,120,0,.9);
  }
  .page-btn:disabled{
    opacity:.4;cursor:not-allowed;
  }
  .page-btn svg{width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:2.6;stroke-linecap:round;stroke-linejoin:round}

  .empty{
    display:none;
    text-align:center;
    padding:60px 24px;
    color:var(--muted);
  }
  .empty.show{display:block}
  .empty .ico{
    width:64px;height:64px;margin:0 auto 14px;
    border-radius:20px;
    background:rgba(245,166,35,.14);
    border:1px solid rgba(224,120,0,.22);
    display:grid;place-items:center;
  }
  .empty .ico svg{
    width:32px;height:32px;
    stroke:var(--amber-2);fill:none;
    stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;
  }
  .empty strong{display:block;color:var(--text-soft);font-size:1rem;font-weight:700;margin-bottom:4px}

  footer{
    border-top:1px solid var(--line);
    background:rgba(250,250,252,.75);
    padding:22px 24px;
    text-align:center;
    font-size:.82rem;
    color:var(--text-soft);
    font-weight:500;
  }
  footer .accent{color:var(--amber-2);font-weight:800}

  @media(max-width:980px){
    .nav-inner{padding:0 18px}
    .hero, .filters-wrap, .list, .pagination{padding-left:18px;padding-right:18px}
    .filters-wrap{top:72px}
  }
  @media(max-width:720px){
    .nav-links .nav-link span{display:none}
    .nav-link{padding:9px}
    .brand-name{font-size:1.05rem}
    .hero{padding-top:32px}
    .filters{gap:8px}
    .search{min-width:100%}
    .filter-tags{width:100%;overflow-x:auto;padding-bottom:2px;flex-wrap:nowrap}
    .filter-tags::-webkit-scrollbar{display:none}
    .tag-btn{flex-shrink:0}
    .card-head{padding:16px 16px;gap:12px}
    .card-ico{width:40px;height:40px;border-radius:10px}
    .card-ico svg{width:20px;height:20px}
    .card-title{font-size:1rem}
    .pane{padding:14px 14px 16px}
    .tabs{padding:0 6px}
    .tab{padding:11px 12px;font-size:.78rem}
    pre{font-size:.72rem;padding:12px}
  }
  @media(prefers-reduced-motion:reduce){
    *,*::before,*::after{
      animation-duration:.001ms !important;
      animation-iteration-count:1 !important;
      transition-duration:.001ms !important;
    }
  }
  :focus-visible{outline:3px solid var(--amber-2);outline-offset:2px;border-radius:6px}
</style>
</head>
<body>

<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <defs>
    <linearGradient id="bvg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#FFE7BE"/>
      <stop offset="48%" stop-color="#F5A623"/>
      <stop offset="100%" stop-color="#E07800"/>
    </linearGradient>
    <symbol id="beaver" viewBox="0 0 64 64">
      <circle cx="13" cy="17.5" r="7.5" fill="url(#bvg)"/>
      <circle cx="51" cy="17.5" r="7.5" fill="url(#bvg)"/>
      <ellipse cx="32" cy="33" rx="23" ry="21" fill="url(#bvg)"/>
      <ellipse cx="32" cy="41" rx="15.5" ry="12" fill="#8A4A00" opacity=".28"/>
      <ellipse cx="32" cy="34.5" rx="4.6" ry="3.2" fill="#2B1606"/>
      <circle cx="22.5" cy="26" r="3.4" fill="#2B1606"/>
      <circle cx="41.5" cy="26" r="3.4" fill="#2B1606"/>
      <circle cx="23.6" cy="25" r="1.2" fill="#FFFCF6"/>
      <circle cx="42.6" cy="25" r="1.2" fill="#FFFCF6"/>
      <rect x="27.3" y="44.5" width="4.3" height="10.5" rx="1.7" fill="#FFFCF6"/>
      <rect x="32.4" y="44.5" width="4.3" height="10.5" rx="1.7" fill="#FFFCF6"/>
    </symbol>

    <symbol id="ico-home" viewBox="0 0 24 24"><path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></symbol>
    <symbol id="ico-book" viewBox="0 0 24 24"><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H19a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H6.5A2.5 2.5 0 0 0 4 22.5V4.5z"/><path d="M4 4.5A2.5 2.5 0 0 0 6.5 7H20"/></symbol>
    <symbol id="ico-gh" viewBox="0 0 24 24"><path d="M12 .5C5.7.5.5 5.7.5 12c0 5.1 3.3 9.4 7.9 10.9.6.1.8-.2.8-.6v-2c-3.2.7-3.9-1.5-3.9-1.5-.5-1.3-1.3-1.7-1.3-1.7-1.1-.7.1-.7.1-.7 1.2.1 1.8 1.2 1.8 1.2 1 1.8 2.7 1.3 3.4 1 .1-.8.4-1.3.7-1.6-2.6-.3-5.3-1.3-5.3-5.8 0-1.3.5-2.3 1.2-3.1-.1-.3-.5-1.5.1-3.1 0 0 1-.3 3.3 1.2a11.5 11.5 0 016 0C17.6 4.7 18.6 5 18.6 5c.6 1.6.2 2.8.1 3.1.8.8 1.2 1.8 1.2 3.1 0 4.5-2.7 5.5-5.3 5.8.4.4.8 1.1.8 2.2v3.3c0 .3.2.7.8.6 4.6-1.5 7.9-5.8 7.9-10.9C23.5 5.7 18.3.5 12 .5z"/></symbol>
    <symbol id="ico-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></symbol>
    <symbol id="ico-route" viewBox="0 0 24 24"><circle cx="6" cy="19" r="2.5"/><circle cx="18" cy="5" r="2.5"/><path d="M8.5 19h5a4 4 0 0 0 0-8h-3a4 4 0 0 1 0-8"/></symbol>
    <symbol id="ico-code" viewBox="0 0 24 24"><path d="M9 18l-6-6 6-6"/><path d="M15 6l6 6-6 6"/></symbol>
    <symbol id="ico-layers" viewBox="0 0 24 24"><path d="M12 3l9 5-9 5-9-5 9-5z"/><path d="M3 13l9 5 9-5"/></symbol>
    <symbol id="ico-shield" viewBox="0 0 24 24"><path d="M12 2l9 4v6c0 5-3.6 8.7-9 10-5.4-1.3-9-5-9-10V6l9-4z"/></symbol>
    <symbol id="ico-check" viewBox="0 0 24 24"><path d="M5 13l4 4 10-10"/></symbol>
    <symbol id="ico-key" viewBox="0 0 24 24"><circle cx="8" cy="15" r="4"/><path d="M11 12l9-9 3 3-3 3 2 2-3 3-2-2-3 3"/></symbol>
    <symbol id="ico-bell" viewBox="0 0 24 24"><path d="M6 16V11a6 6 0 0 1 12 0v5l2 2H4l2-2z"/><path d="M10 20a2 2 0 0 0 4 0"/></symbol>
    <symbol id="ico-terminal" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 9l3 3-3 3"/><path d="M13 15h4"/></symbol>
    <symbol id="ico-copy" viewBox="0 0 24 24"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></symbol>
    <symbol id="ico-chev-l" viewBox="0 0 24 24"><path d="M15 6l-6 6 6 6"/></symbol>
    <symbol id="ico-chev-r" viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></symbol>
  </defs>
</svg>

<header>
  <div class="nav-inner">
    <a href="/" class="brand" aria-label="Beaver Framework — início">
      <span class="brand-logo"><svg aria-hidden="true"><use href="#beaver"/></svg></span>
      <span class="brand-text">
        <span class="brand-name">Beaver<span class="accent">.</span></span>
        <span class="brand-sub">Framework</span>
      </span>
    </a>

    <nav class="nav-links" aria-label="Navegação principal">
      <a class="nav-link" href="/">
        <svg aria-hidden="true"><use href="#ico-home"/></svg>
        <span>Home</span>
      </a>
      <a class="nav-link" href="/documentation.php">
        <svg aria-hidden="true"><use href="#ico-book"/></svg>
        <span>Documentação</span>
      </a>
      <a class="nav-link active" href="/sdk.php" aria-current="page">
        <svg aria-hidden="true"><use href="#ico-terminal"/></svg>
        <span>SDK</span>
      </a>
      <a class="nav-cta" href="https://github.com/Frank-Onidesk/beaver-framework" target="_blank" rel="noopener noreferrer">
        <svg aria-hidden="true"><use href="#ico-gh"/></svg>
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
      <svg aria-hidden="true"><use href="#ico-search"/></svg>
      <input
        id="q"
        type="search"
        placeholder="Pesquisar no SDK…"
        autocomplete="off"
        aria-label="Pesquisar no SDK"
      >
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
    style="animation-delay:<?= $i * 40 ?>ms"
  >
    <header class="card-head">
      <span class="card-ico" aria-hidden="true">
        <svg><use href="#ico-<?= htmlspecialchars($c['icon']) ?>"/></svg>
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
        <svg aria-hidden="true"><use href="#ico-code"/></svg>
        Definição
      </button>
      <button class="tab" role="tab" aria-selected="false" data-pane="instance" type="button">
        <svg aria-hidden="true"><use href="#ico-layers"/></svg>
        Instância
      </button>
      <button class="tab" role="tab" aria-selected="false" data-pane="example" type="button">
        <svg aria-hidden="true"><use href="#ico-check"/></svg>
        Exemplo
      </button>
    </div>

    <div class="panes">
      <div class="pane active" data-pane="component" role="tabpanel">
        <div class="code-block">
          <div class="code-head">
            <span class="code-lang"><i></i> Definição</span>
            <button class="copy-btn" type="button" data-copy>
              <svg aria-hidden="true"><use href="#ico-copy"/></svg> copiar
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
              <svg aria-hidden="true"><use href="#ico-copy"/></svg> copiar
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
              <svg aria-hidden="true"><use href="#ico-copy"/></svg> copiar
            </button>
          </div>
          <pre><?= htmlspecialchars(trim($c['example'])) ?></pre>
        </div>
      </div>
    </div>
  </article>
  <?php endforeach; ?>

  <div class="empty" id="empty">
    <div class="ico"><svg aria-hidden="true"><use href="#ico-search"/></svg></div>
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
    <svg aria-hidden="true"><use href="#ico-chev-l"/></svg>
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
    <svg aria-hidden="true"><use href="#ico-chev-r"/></svg>
  </a>
</nav>
<?php endif; ?>

<footer>
  © <span id="ano"></span> Beaver Framework · Feito com <span class="accent">🦫</span> em Portugal
</footer>

<script>
document.querySelectorAll('.card').forEach(card => {
  const tabs  = card.querySelectorAll('.tab');
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

const q        = document.getElementById('q');
const tagsWrap = document.getElementById('tags');
const list     = document.getElementById('list');
const empty    = document.getElementById('empty');
const count    = document.getElementById('count');
const cards    = Array.from(document.querySelectorAll('.card'));

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
    const matchTag  = activeTag === 'all' || tags.includes(activeTag);
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
