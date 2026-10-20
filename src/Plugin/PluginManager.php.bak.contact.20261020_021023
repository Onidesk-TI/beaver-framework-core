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
use Beaver\Sdk\ManifestValidator;
use Beaver\Sdk\Context;
use Beaver\Sdk\PermissionAuditor;

class PluginManager
{
    private array $plugins = [];
    private bool $discovered = false;
    private bool $booted = false;
    private ?PermissionAuditor $auditor = null;
    private ?array $stateCache = null;

    public function __construct(private Application $app)
    {
    }

    public function discover(): self
    {
        if ($this->discovered) {
            return $this;
        }
        $this->discovered = true;

        $mode = $this->app->config('app.plugins.mode', 'prod');

        // Ordem importa: o primeiro plugin encontrado com um dado slug ganha.
        // Por isso 'internal_path' vem primeiro — plugins internos do framework
        // não podem ser sobrepostos por plugins externos.
        $paths = match ($mode) {
            'dev' => [
                ['path' => $this->app->config('app.plugins.internal_path'), 'source' => 'internal'],
                ['path' => $this->app->config('app.plugins.dev_path'),      'source' => 'dev'],
                ['path' => $this->app->config('app.plugins.staging_path'),  'source' => 'staging'],
                ['path' => $this->app->config('app.plugins.path'),          'source' => 'prod'],
            ],
            'staging' => [
                ['path' => $this->app->config('app.plugins.internal_path'), 'source' => 'internal'],
                ['path' => $this->app->config('app.plugins.staging_path'),  'source' => 'staging'],
                ['path' => $this->app->config('app.plugins.path'),          'source' => 'prod'],
            ],
            default => [
                ['path' => $this->app->config('app.plugins.internal_path'), 'source' => 'internal'],
                ['path' => $this->app->config('app.plugins.path'),          'source' => 'prod'],
            ],
        };

        foreach ($paths as $entry) {
            $base = $entry['path'];
            if (!$base || !is_dir($base)) {
                continue;
            }

            foreach (glob($base . '/*', GLOB_ONLYDIR) as $dir) {
                $slug = basename($dir);
                if (str_starts_with($slug, '.')) {
                    continue;
                }
                if (isset($this->plugins[$slug])) {
                    continue;
                }

                $manifestFile = $dir . '/plugin.json';
                if (!is_file($manifestFile)) {
                    continue;
                }

                $manifest = json_decode((string) file_get_contents($manifestFile), true);
                if (!is_array($manifest) || empty($manifest['slug'])) {
                    log_warning("Manifest inválido", ["file" => $manifestFile]);
                    continue;
                }

                $this->plugins[$manifest['slug']] = [
                    'path'     => $dir,
                    'manifest' => $manifest,
                    'instance' => null,
                    'source'   => $entry['source'],
                ];
            }
        }

        return $this;
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }
        $this->booted = true;

        foreach ($this->plugins as $slug => $data) {
            // Respeitar o estado enabled/disabled (storage/plugins.json)
            if (!$this->isPluginEnabled($slug, $data['source'] ?? '')) {
                log_info("Plugin desativado", ["slug" => $slug]);
                continue;
            }

            try {
                $instance = $this->instantiate($data);
                $this->plugins[$slug]['instance'] = $instance;

                // SDK: atribuir contexto de permissões antes do boot
                $instance->setContext($this->createContext($data['manifest']));

                $instance->boot();
            } catch (\Throwable $e) {
                log_error("Plugin falhou", ["slug" => $slug, "error" => $e->getMessage()]);
            }
        }
    }

    private function instantiate(array $data): PluginBase
    {
        $manifest = $data['manifest'];
        $path     = $data['path'];

        // --- SDK: validação de manifesto ---
        $errors = ManifestValidator::validate($manifest);
        if ($errors) {
            throw new \RuntimeException(
                "plugin.json inválido: " . implode('; ', $errors)
            );
        }
        // --- /SDK ---

        $pluginAutoload = $path . '/vendor/autoload.php';
        if (is_file($pluginAutoload)) {
            require_once $pluginAutoload;
        }

        $mainFile = $path . '/' . ($manifest['main'] ?? 'Plugin.php');
        if (!is_file($mainFile)) {
            throw new \RuntimeException("Main file não existe: $mainFile");
        }

        require_once $mainFile;

        $namespace = $manifest['namespace'] ?? null;
        $main      = $manifest['main'] ?? null;

        if (!$namespace || !$main) {
            throw new \RuntimeException("Manifest sem 'namespace' ou 'main'");
        }

        $className = $namespace . '\\' . basename($main, '.php');

        if (!class_exists($className)) {
            throw new \RuntimeException("Classe não encontrada: $className");
        }

        $instance = new $className($path, $manifest);

        if (!$instance instanceof PluginBase) {
            throw new \RuntimeException("$className deve estender PluginBase");
        }

        return $instance;
    }

    public function all(): array
    {
        $out = [];
        foreach ($this->plugins as $slug => $data) {
            if ($data['instance'] instanceof PluginBase) {
                $out[$slug] = $data['instance'];
            }
        }
        return $out;
    }

    public function get(string $slug): ?PluginBase
    {
        return $this->plugins[$slug]['instance'] ?? null;
    }

    public function has(string $slug): bool
    {
        return isset($this->plugins[$slug]);
    }

    public function manifests(): array
    {
        return array_map(fn($p) => [
            'slug'    => $p['manifest']['slug']    ?? '',
            'name'    => $p['manifest']['name']    ?? '',
            'version' => $p['manifest']['version'] ?? '',
            'path'    => $p['path'],
            'source'  => $p['source'],
            'booted'  => $p['instance'] instanceof PluginBase,
        ], $this->plugins);
    }

    public function mode(): string
    {
        return $this->app->config('app.plugins.mode', 'prod');
    }

    // ---------- permissões (SDK) ----------

    /**
     * Devolve o PermissionAuditor partilhado (cria se não existir).
     */
    public function auditor(): PermissionAuditor
    {
        return $this->auditor ??= new PermissionAuditor();
    }

    /**
     * Cria o Context de um plugin conforme o mode configurado.
     */
    private function createContext(array $manifest): Context
    {
        $mode = (string) $this->app->config('app.plugins.permissions.mode', 'audit');

        if (!in_array($mode, ['off', 'audit', 'enforce'], true)) {
            $mode = 'audit';
        }

        $granted = $this->normalizePermissions($manifest['permissions'] ?? []);

        return new Context(
            slug:    $manifest['slug'] ?? '',
            granted: $granted,
            mode:    $mode,
            auditor: $this->auditor(),
        );
    }

    /**
     * Achata as permissões do manifesto num array plano de strings.
     * Suporta o formato rico: {"filesystem": {"read": [...]}}
     */
    private function normalizePermissions(mixed $perms): array
    {
        if (!is_array($perms)) {
            return [];
        }

        $flat = [];
        foreach ($perms as $key => $value) {
            if (is_int($key)) {
                if (is_string($value)) {
                    $flat[] = $value;
                }
                continue;
            }

            if (is_bool($value)) {
                $flat[] = $key;
            } elseif (is_string($value)) {
                $flat[] = "$key.$value";
            } elseif (is_array($value)) {
                if (array_is_list($value)) {
                    $flat[] = $key;
                } else {
                    foreach (array_keys($value) as $subKey) {
                        $flat[] = "$key.$subKey";
                    }
                }
            }
        }

        return array_values(array_unique($flat));
    }

    // ---------- state (storage/plugins.json) ----------

    /**
     * Verifica se um plugin pode arrancar.
     * Regras:
     *   - Nativos (internal/dev): enabled por omissão, exceto se em `disabled`
     *   - Externos: só arrancam se listados em `enabled`
     */
    /**
     * Consulta pública: o plugin está ativo segundo o estado atual?
     *
     * Regras (iguais às internas):
     *   - Nativo (internal/dev): ativo por omissão, exceto se em `disabled`
     *   - Externo: ativo só se listado em `enabled`
     */
    public function isEnabled(string $slug, string $source = 'internal'): bool
    {
        return $this->isPluginEnabled($slug, $source);
    }

    private function isPluginEnabled(string $slug, string $source): bool
    {
        $state = $this->loadState();

        $inEnabled  = in_array($slug, $state['enabled']  ?? [], true);
        $inDisabled = in_array($slug, $state['disabled'] ?? [], true);

        // Externos: só se em enabled
        if (!in_array($source, ['internal', 'dev'], true)) {
            return $inEnabled && !$inDisabled;
        }

        // Nativos: ligados por omissão, exceto se explicitamente disabled
        return !$inDisabled;
    }

    private function loadState(): array
    {
        if ($this->stateCache !== null) {
            return $this->stateCache;
        }

        $file = dirname(__DIR__, 2) . '/storage/plugins.json';
        if (!is_file($file)) {
            return $this->stateCache = ['enabled' => [], 'disabled' => [], 'installed' => []];
        }

        $data = json_decode((string) file_get_contents($file), true);
        return $this->stateCache = is_array($data)
            ? $data
            : ['enabled' => [], 'disabled' => [], 'installed' => []];
    }
}
