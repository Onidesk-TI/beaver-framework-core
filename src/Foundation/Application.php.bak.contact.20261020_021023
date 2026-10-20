<?php

/**
 * Beaver Framework — Modern PHP framework with a plugin ecosystem.
 *
 * @package    Beaver Framework
 * @author     Franco <onidesk@outlook.com>
 * @copyright  2026 Onidesk
 * @license    MIT <https://opensource.org/licenses/MIT>
 * @link       https://github.com/Onidesk-TI/beaver-framework
 */

namespace Beaver\Foundation;

use Beaver\Http\{Kernel, Request, Router, Response};
use Beaver\View\Assets;
use Beaver\I18n\Locale;
use Beaver\I18n\Translator;
use Beaver\Plugin\PluginManager;
use Beaver\Plugin\Hooks;
use Beaver\View\View;
use Beaver\Hashing\Hasher;
use Beaver\Hashing\HasherFactory;
use Beaver\Admin\AdminLayout;
use Beaver\Auth\Auth;
use Beaver\Auth\Drivers\SessionAuthDriver;
use Beaver\Error\ErrorRenderer;
use Beaver\Session\Session;
use Beaver\Session\Drivers\NativeDriver;
use Beaver\Cache\Cache;
use Beaver\Cache\CacheFactory;
use Beaver\Logging\Logger;
use Beaver\Logging\LogFactory;
use Beaver\Storage\Filesystem;
use Beaver\Storage\StorageFactory;
use Beaver\Encryption\Encrypter;
use Beaver\Cookies\CookieJar;
use Beaver\Mail\Mailer;
use Beaver\Mail\MailFactory;
use Beaver\RateLimiter\RateLimiter;
use Beaver\RateLimiter\RateLimiterFactory;
use Beaver\Queue\Queue;
use Beaver\Queue\QueueFactory;
use Beaver\Database\Database;

class Application
{
    private static ?self $instance = null;

    private array $bindings = [];
    private array $instances = [];

    public Config $config;
    public string $basePath;      // alias de $projectPath
    public string $projectPath;   // raiz do projeto (app/, config/, routes/, storage/)
    public string $frameworkPath; // raiz do framework (src/, sdk/, plugins/, stubs/)

    public function __construct(?string $projectPath = null, ?string $frameworkPath = null)
    {
        // Compatibilidade: se só um path é passado, framework = projeto.
        $this->projectPath   = $projectPath   ?? dirname(__DIR__, 2);
        $this->frameworkPath = $frameworkPath ?? $this->projectPath;
        $this->basePath      = $this->projectPath;   // alias BC
        self::$instance = $this;

        //  Load .env first, then register global helpers.
        \Beaver\Foundation\Env::load($this->basePath . '/.env');
        require_once __DIR__ . '/helpers.php';

        //  Config
        $this->config = new Config($this->basePath . '/config');
        $this->config->load();
        $this->instances[Config::class] = $this->config;

        //  Timezone
        date_default_timezone_set($this->config->get('app.timezone', 'UTC'));

        //  Carbon locale (depois de a config existir)
        if (class_exists(\Carbon\CarbonImmutable::class)) {
            \Carbon\CarbonImmutable::setLocale(
                $this->config->get('app.locale', 'pt')
            );
        }

        //  View
        $this->instances[View::class] = new View(
            $this->config->get('app.views.path'),
            $this->config->get('app.views.cache')
        );

        //  Tema ativo (regista ThemeManager + namespace 'theme')
        $this->bootTheme();

        //  Router
        $this->instances[Router::class] = Router::current();

        //  Hooks
        $this->instances[Hooks::class] = new Hooks();

            //  Plugin Manager
        $this->instances[PluginManager::class] = new PluginManager($this);

             //  Admin layout (lê o hook ADMIN_MENU dos plugins)
        $this->instances[AdminLayout::class] = new AdminLayout($this);

        //  Hashing
        //  API híbrida: helper (hash_make), container, DI
        $this->instances[Hasher::class] = HasherFactory::fromConfig(
            $this->config->get('hashing', [])
        );
        require_once __DIR__ . '/../Hashing/helpers.php';


         //  Auth (driver de sessão)
        Auth::register(new SessionAuthDriver());

       //  Session
        $this->instances[Session::class] = new Session(new NativeDriver());
        require_once __DIR__ . '/../Session/helpers.php';

         //  Cache
        $this->instances[Cache::class] = new Cache(
            CacheFactory::fromConfig($this->config->get('cache', []))
        );
        require_once __DIR__ . '/../Cache/helpers.php';

                //  Logging
        $this->instances[Logger::class] = new Logger(
            LogFactory::fromConfig($this->config->get('logging', []))
        );
        require_once __DIR__ . '/../Logging/helpers.php';

        //  Storage
        $this->instances[Filesystem::class] = StorageFactory::fromConfig(
            $this->config->get('filesystems', [])
        );
        require_once __DIR__ . '/../Storage/helpers.php';
        //  Encryption
        $encryptionKey = (string) $this->config->get('encryption.key', '');
        if ($encryptionKey !== '') {
            $this->instances[Encrypter::class] = new Encrypter($encryptionKey);
        }
        require_once __DIR__ . '/../Encryption/helpers.php';


        //  Cookies
        $this->instances[CookieJar::class] = new CookieJar(
            $this->config->get('cookies', [])
        );
        require_once __DIR__ . '/../Cookies/helpers.php';

        //Validation
        require_once __DIR__ . '/../Validation/helpers.php';


        //  Mail
        $this->instances[Mailer::class] = MailFactory::fromConfig(
            $this->config->get('mail', [])
        );
        require_once __DIR__ . '/../Mail/helpers.php';

         //  RateLimiter
        $this->instances[RateLimiter::class] = new RateLimiter(
            RateLimiterFactory::fromConfig(
                $this->config->get('rate_limiter', []),
                $this->make(Cache::class),
            )
        );
        require_once __DIR__ . '/../RateLimiter/helpers.php';


              //  Queue
        $queueDb = null;
        try {
            $queueDb = \Beaver\Database\Database::getInstance();
        } catch (\Throwable) {
            // Sem BD — sync/array continuam a funcionar
        }

        $this->instances[Queue::class] = new Queue(
            QueueFactory::fromConfig($this->config->get('queue', []), $queueDb)
        );
        require_once __DIR__ . '/../Queue/helpers.php';


     //  i18n
        $this->bootI18n();


        //error handling
        $this->instances[ErrorRenderer::class] = new ErrorRenderer($this);

        // assets
        \Beaver\View\Assets::setBasePath($this->basePath);
    }



    public static function getInstance(): self
    {
        if (self::$instance === null) {
            // Fallback: o framework a autoarrancar (sem projeto externo).
            $root = dirname(__DIR__, 2);
            self::$instance = new self($root, $root);
        }
        return self::$instance;
    }

    public function bind(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    public function instance(string $abstract, mixed $object): void
    {
        $this->instances[$abstract] = $object;
    }

    public function make(string $abstract): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        if (isset($this->bindings[$abstract])) {
            return $this->instances[$abstract] = ($this->bindings[$abstract])($this);
        }
        if (class_exists($abstract)) {
            return $this->instances[$abstract] = new $abstract();
        }
        throw new \RuntimeException("Cannot resolve: $abstract");
    }

    public function config(?string $key = null, mixed $default = null): mixed
    {
        return $key === null
            ? $this->config->all()
            : $this->config->get($key, $default);
    }


  /**
 * Regista o sistema de temas.
 *
 * - Cria o ThemeManager e guarda-o no container.
 * - Se houver tema ativo, regista o namespace 'theme' no View.
 *
 * Não faz nada se o projeto não tiver config/themes.php.
 */
    protected function bootTheme(): void
    {
        $config = $this->config->get('themes');
        if (!is_array($config)) {
            return;
        }

        $paths = \Beaver\Sdk\Theme\ThemePaths::fromConfig($config);
        $mgr   = new \Beaver\Sdk\Theme\ThemeManager($paths);

        // Registar no container (antes de qualquer uso)
        $this->instances[\Beaver\Sdk\Theme\ThemeManager::class] = $mgr;

        $slug = $mgr->active();
        if ($slug === null) {
            return;
        }

        $dir = $mgr->path($slug);
        if ($dir === null) {
            return;
        }

        $viewsDir = $dir . '/resources/views';
        if (is_dir($viewsDir)) {
            View::registerNamespace('theme', $viewsDir);
        }
    }

    public function boot(): void
    {
        if ($this->config->get('app.plugins.autoload', true)) {
            $this->make(PluginManager::class)->discover()->boot();
        }

        $routesFile = $this->config->get('app.routes.web');
        if (is_file($routesFile)) {
            require $routesFile;
        }
    }

    public function handleHttp(?Request $request = null): void
    {
        $kernel   = new Kernel($this);
        $response = $kernel->handle($request ?? Request::capture());

     // Injeta assets (CSS/JS) automaticamente em respostas HTML
        $this->injectAssets($response);

        $response->send();
    }

/**
 * Se a resposta for HTML, injeta os assets registrados via Assets::css()/js()
 * antes do </head>.
 */
    private function injectAssets(Response $response): void
    {
        $ct = $response->headers['Content-Type'] ?? '';

        if (stripos($ct, 'text/html') === false) {
            return;
        }
        if ($response->content === '' || !str_contains($response->content, '<')) {
            return;
        }

        $response->content = \Beaver\View\Assets::inject($response->content);
    }

    /**
     * Boot the internationalization layer.
     *
     * Resolution order for the language files:
     *   1. app.lang.path from config (if set)
     *   2. <project>/lang   (project overrides)
     *   3. <framework>/lang (framework defaults)
     */
    protected function bootI18n(): void
    {
        $langPath = $this->config->get('app.lang.path')
            ?? (is_dir($this->basePath . '/lang')
                ? $this->basePath . '/lang'
                : dirname(__DIR__, 2) . '/lang');

        $locale = getenv('BEAVER_LANG') ?: Locale::fromBrowser();

        $translator = new Translator(
            langPath: $langPath,
            locale:   $locale,
            fallback: $this->config->get('app.lang.fallback', 'pt'),
        );

        $this->instances[Translator::class] = $translator;
        Translator::boot($translator);

        $helpers = dirname(__DIR__) . '/I18n/helpers.php';
        if (is_file($helpers)) {
            require_once $helpers;
        }
    }
}
