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

namespace Beaver\Plugin;

use Beaver\Foundation\Application;
use Beaver\Sdk\Context;
use Beaver\Sdk\ScopedHooks;
use Beaver\Http\Router;
use Beaver\View\View;

abstract class PluginBase
{
    public string $path;
    public array $manifest = [];
    private ?Context $context = null;

    public function __construct(string $path, array $manifest)
    {
        $this->path     = $path;
        $this->manifest = $manifest;
    }

    abstract public function boot(): void;

    // ---------- helpers para plugins ----------

    public function slug(): string
    {
        return $this->manifest['slug'] ?? basename($this->path);
    }

    public function name(): string
    {
        return $this->manifest['name'] ?? $this->slug();
    }

    public function version(): string
    {
        return $this->manifest['version'] ?? '0.0.0';
    }

    public function app(): Application
    {
        return Application::getInstance();
    }

    /**
     * Devolve os hooks do plugin.
     *
     * Se um Context tiver sido atribuído (pelo PluginManager), devolve
     * um ScopedHooks que aplica o modo audit/enforce.
     * Caso contrário, devolve o Hooks cru (compatibilidade).
     */
    public function hooks(): Hooks|ScopedHooks
    {
        $inner = $this->app()->make(Hooks::class);

        if ($this->context === null) {
            return $inner;
        }

        return new ScopedHooks($inner, $this->context);
    }

    public function router(): Router
    {
        return Router::current();
    }

    /** Carrega rotas de routes/web.php (recebe $router e $plugin) */
    public function loadRoutes(?string $file = null): void
    {
        $file ??= $this->path . '/routes/web.php';
        if (!is_file($file)) {
            return;
        }

        $router = $this->router();
        $plugin = $this;

        require $file;
    }

    /** Regista views com namespace: view('sms::modal') */
    public function loadViews(?string $path = null): void
    {
        $path ??= $this->path . '/resources/views';
        View::registerNamespace($this->slug(), $path);
    }

 /**
     * Register the plugin's lang/ folder as a translation namespace.
     *
     * Expects files at: <plugin>/lang/<locale>.php, e.g.:
     *   beaver-plugins/sms/lang/pt.php
     *   beaver-plugins/sms/lang/en.php
     *
     * Keys become accessible as '<slug>::<key>', e.g. __('sms::welcome').
     *
     * @param string|null $path Optional custom lang folder; defaults to <plugin>/lang
     */
    public function loadTranslations(?string $path = null): void
    {
        $path ??= $this->path . '/lang';

        // Silently skip plugins that don't ship translations yet.
        if (!is_dir($path)) {
            return;
        }

        \Beaver\I18n\Translator::addNamespace($this->slug(), $path);
    }

    /**
     * Registra as migrations do plugin no MigrateCommand.
     *
     * As migrations vivem em `<plugin>/database/migrations/*.php` e
     * são executadas por `php beaver migrate`.
     */
    public function loadMigrations(?string $path = null): void
    {
        $path ??= $this->path . '/database/migrations';

        if (!is_dir($path)) {
            return;
        }

        \Beaver\Console\Commands\MigrateCommand::addPluginPath(
            $this->slug(),
            $path
        );
    }

    /** Config do plugin */
    public function config(?string $key = null, mixed $default = null): mixed
    {
        static $cache = [];
        $slug = $this->slug();

        if (!isset($cache[$slug])) {
            $file = $this->path . '/config/settings.php';
            $cache[$slug] = is_file($file) ? require $file : [];
        }

        if ($key === null) {
            return $cache[$slug];
        }

        $segments = explode('.', $key);
        $value = $cache[$slug];
        foreach ($segments as $seg) {
            if (!is_array($value) || !array_key_exists($seg, $value)) {
                return $default;
            }
            $value = $value[$seg];
        }
        return $value;
    }

    // ---------- permissões (SDK) ----------

    /**
     * Atribui o contexto de permissões. Chamado pelo PluginManager.
     */
    public function setContext(Context $context): void
    {
        $this->context = $context;
    }

    /**
     * Devolve o contexto atual (ou null se ainda não foi atribuído).
     */
    public function context(): ?Context
    {
        return $this->context;
    }

    /**
     * Verifica se o plugin tem uma permissão (ex: 'db.write').
     * Sem Context, devolve false por omissão (seguro).
     */
    public function hasPermission(string $scope): bool
    {
        return $this->context?->has($scope) ?? false;
    }
}
