# SqlWizard Lite — Assets & Rotas

Documentação do sistema de rotas e assets do plugin SqlWizard Lite.

============================================================
VISÃO GERAL
============================================================

O SqlWizard Lite é um plugin Beaver que serve:
  - UI     -> página de canvas principal
  - Assets -> CSS, JS e imagens do plugin

Cada plugin do Beaver é responsável por registar as suas próprias
rotas — não há padrão global /plugins/.

============================================================
ESTRUTURA DE FICHEIROS
============================================================

sqlwizard-lite/
  plugin.json                    <- Manifest (slug, namespace, main, permissions)
  composer.json                  <- Autoload PSR-4
  LICENSE                        <- GPL-3.0-or-later
  .gitignore
  routes/
    web.php                      <- Rotas HTTP (UI + assets)
  src/
    SqlWizardLitePlugin.php      <- Classe principal (extends PluginBase)
    Http/
      AssetHelper.php            <- Helper de assets (AssetHelper::serve())
  resources/
    ui/
      css/
        canvas.css
      js/
        canvas.js
      img/
        sqlwizard.png
    views/
      index.php                  <- View principal do canvas

============================================================
URLS FINAIS
============================================================

UI principal  ->  http://localhost:9000/sqlwizard-lite
CSS           ->  http://localhost:9000/plugins/sqlwizard-lite/css/canvas.css
JS            ->  http://localhost:9000/plugins/sqlwizard-lite/js/canvas.js
IMG           ->  http://localhost:9000/plugins/sqlwizard-lite/img/sqlwizard.png

Nota: os assets usam o prefixo /plugins/{slug}/... — este é o padrão
do Beaver para assets de plugins.

============================================================
routes/web.php
============================================================

<?php
declare(strict_types=1);

use Beaver\Http\Response;
use Beaver\Plugins\SqlWizardLite\Http\AssetHelper;

// UI principal
$router->get('/sqlwizard-lite', function () use ($plugin) {
    ob_start();
    require $plugin->path . '/resources/views/index.php';
    return Response::html(ob_get_clean());
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

Pontos-chave:
  - use ($plugin)     -> captura a instância do plugin injetada por loadRoutes()
  - $plugin->path     -> caminho absoluto da raiz do plugin
  - Prefixos exclusivos, não colidem com o full

============================================================
resources/views/index.php (topo)
============================================================

<?php
declare(strict_types=1);

$assetBase = '/plugins/sqlwizard-lite';
?>
<!DOCTYPE html>
...
<link rel="stylesheet" href="<?= $assetBase ?>/css/canvas.css">
...
<script src="<?= $assetBase ?>/js/canvas.js" defer></script>

Importante: o $assetBase tem de usar o mesmo prefixo que a rota de assets.

============================================================
src/Http/AssetHelper.php
============================================================

Classe estática que serve assets com validação de segurança:

<?php
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
            ->withHeader('Content-Type', $mime)
            ->withHeader('Cache-Control', 'public, max-age=3600');
    }
}

============================================================
src/SqlWizardLitePlugin.php
============================================================

<?php
declare(strict_types=1);

namespace Beaver\Plugins\SqlWizardLite;

use Beaver\Plugin\PluginBase;

final class SqlWizardLitePlugin extends PluginBase
{
    public function boot(): void
    {
        $this->loadRoutes();
        $this->loadViews();
    }
}

Herança obrigatória: o PluginManager faz $instance instanceof PluginBase.
Se falhar, o plugin é ignorado silenciosamente.

============================================================
plugin.json
============================================================

{
    "slug": "sqlwizard-lite",
    "name": "SqlWizard Lite",
    "version": "0.1.0",
    "author": "Franco . OniDesk",
    "description": "Editor visual de schema SQL minimalista — apenas canvas e 1 draft local.",
    "namespace": "Beaver\\Plugins\\SqlWizardLite",
    "main": "src/SqlWizardLitePlugin.php",
    "beaver_version": ">=1.0.0",
    "permissions": [
        "routes.register",
        "views.render"
    ]
}

Campos obrigatórios (validados pelo ManifestValidator):
  name, slug, version, namespace, main, beaver_version

============================================================
INSTALAÇÃO
============================================================

1. Clonar para plugins/

   cd /var/www/onidesk/beaver-framework/plugins
   git clone git@github.com:Onidesk-TI/sqlwizard-lite.git

   ou via symlink (dev):

   ln -sfn /var/www/onidesk/sqlwizard-lite \
           /var/www/onidesk/beaver-framework/plugins/sqlwizard-lite

2. Registar o namespace PSR-4 no Beaver

   Editar /var/www/onidesk/beaver-framework/composer.json
   Dentro do autoload.psr-4, adicionar:

   "Beaver\\Plugins\\SqlWizardLite\\": "plugins/sqlwizard-lite/src/",

   Depois:

   cd /var/www/onidesk/beaver-framework
   composer dump-autoload

3. Confirmar que aparece no autoload

   grep "SqlWizardLite" vendor/composer/autoload_psr4.php

   Esperado:
   'Beaver\\Plugins\\SqlWizardLite\\' => array($baseDir . '/plugins/sqlwizard-lite/src'),

============================================================
DESCOBERTA AUTOMÁTICA
============================================================

O PluginManager do Beaver percorre plugins/*/plugin.json e:

  1. Valida o manifesto (ManifestValidator::validate)
  2. Requer o ficheiro main (src/SqlWizardLitePlugin.php)
  3. Instancia a classe ($namespace . '\' . basename($main, '.php'))
  4. Chama boot() — que por sua vez chama loadRoutes()

Se algum passo falhar, o plugin é silenciosamente ignorado.
Logs vão para error_log:

  [PluginManager] Plugin 'sqlwizard-lite' falhou: <razão>

============================================================
TESTES (curl)
============================================================

UI:
  curl -s -o /dev/null -w "UI:  %{http_code}\n"  http://localhost:9000/sqlwizard-lite
  -> 200

Assets:
  curl -s -o /dev/null -w "CSS: %{http_code}\n" http://localhost:9000/plugins/sqlwizard-lite/css/canvas.css
  curl -s -o /dev/null -w "JS:  %{http_code}\n"  http://localhost:9000/plugins/sqlwizard-lite/js/canvas.js
  curl -s -o /dev/null -w "IMG: %{http_code}\n" http://localhost:9000/plugins/sqlwizard-lite/img/sqlwizard.png
  -> todos 200

Conteúdo:
  curl -s http://localhost:9000/plugins/sqlwizard-lite/css/canvas.css | head -3
  -> *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

============================================================
ERROS COMUNS E SOLUÇÕES
============================================================

Erro: 404 Not Found do servidor PHP (não do Beaver)

  Causa: php -S tenta servir o ficheiro estático antes de passar ao index.php.
  Solução: arrancar com router script:

    cd /var/www/onidesk/beaver-framework
    php -S localhost:9000 -t public/ public/router.php

  Criar public/router.php:

    <?php
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $file = __DIR__ . $path;

    if ($path !== '/' && is_file($file)) {
        return false;
    }

    require __DIR__ . '/index.php';

Erro: Class not found no autoload

  Causa: namespace não registado no composer.json do Beaver.
  Solução: adicionar a linha PSR-4 e correr composer dump-autoload.

Erro: plugin.json inválido

  Causa: falta um dos campos obrigatórios.
  Solução: completar o manifesto.
  Campos: name, slug, version, namespace, main, beaver_version.

Erro: conflito de prefixo com o full

  Causa: os dois plugins (full e Lite) usam prefixos parecidos.
  Solução: full usa /plugins/sqlwizard/... e /sqlwizard.
           Lite usa /plugins/sqlwizard-lite/... e /sqlwizard-lite.
           Nunca colidem.

============================================================
ESTADO FINAL (TESTADO)
============================================================

GET /sqlwizard-lite                              -> 200 OK
GET /plugins/sqlwizard-lite/css/canvas.css       -> 200 OK
GET /plugins/sqlwizard-lite/js/canvas.js         -> 200 OK
GET /plugins/sqlwizard-lite/img/sqlwizard.png    -> 200 OK

============================================================
LIÇÕES APRENDIDAS
============================================================

1.  O PluginManager descobre plugins automaticamente — basta
    plugin.json válido em plugins/{slug}/.

2.  O plugin.json exige namespace e main — sem eles, o plugin
    é ignorado silenciosamente.

3.  A classe do plugin tem de estender PluginBase — o construtor
    recebe ($path, $manifest).

4.  loadRoutes() injeta $router e $plugin no escopo do require —
    daí o use ($plugin) funcionar.

5.  O php -S não faz rewrite por omissão — precisa de router
    script (equivalente ao .htaccess).

6.  Cada plugin registra o SEU prefixo — não há padrão global
    /plugins/.

7.  Assets de plugins usam /plugins/{slug}/... — convenção do
    Beaver para separar por plugin.

8.  O AssetHelper::serve() deve validar com preg_match — evita
    path traversal.

============================================================
VER TAMBÉM
============================================================

  ../beaver-framework/src/Plugin/PluginManager.php
  ../beaver-framework/src/Plugin/PluginBase.php
  ../beaver-framework/sdk/src/ManifestValidator.php
  ../beaver-framework/src/Http/Router.php
  https://github.com/Onidesk-TI/sqlwizard

============================================================

Documentação gerada em 2026-09-26.
Última atualização: após resolução bem-sucedida das rotas de assets.

