<?php

/**
 * Beaver Framework — ThemeManager
 *
 * Descobre temas instalados, gere o tema ativo e persiste o estado
 * em storage/themes.json.
 *
 * Modelo conceptual alinhado com o PluginManager:
 *   - mode controla a ordem dos paths (dev/staging/prod)
 *   - internal_path vem primeiro (não pode ser sobreposto)
 *   - cache em memória por request
 *
 * @package    Beaver\Sdk\Theme
 * @version    0.1.0
 * @author     Franco <onidesk@outlook.com>
 * @copyright  2026 Onidesk
 * @license    MIT <https://opensource.org/licenses/MIT>
 */

declare(strict_types=1);

namespace Beaver\Sdk\Theme;

final class ThemeManager
{
    /** @var array<string, ThemeManifest>|null */
    private static ?array $cache = null;

    public function __construct(private ThemePaths $paths) {}

    // ---------- descoberta ----------

    /**
     * @return array<string, ThemeManifest>
     */
    public function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $themes = [];

        foreach ($this->paths->searchPaths() as $base) {
            if (!is_dir($base)) {
                continue;
            }

            foreach (glob($base . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
                $slug = basename($dir);
                if (str_starts_with($slug, '.')) {
                    continue;
                }
                if (isset($themes[$slug])) {
                    continue;
                }

                $manifestFile = $dir . '/theme.json';
                if (!is_file($manifestFile)) {
                    continue;
                }

                $data = json_decode((string) file_get_contents($manifestFile), true);
                if (!is_array($data)) {
                    error_log("[ThemeManager] theme.json ilegível: $manifestFile");
                    continue;
                }

                $errors = ThemeValidator::validate($data);
                if ($errors !== []) {
                    error_log("[ThemeManager] theme.json inválido em $manifestFile: " . implode('; ', $errors));
                    continue;
                }

                $themes[$slug] = ThemeManifest::fromArray($data);
            }
        }

        return self::$cache = $themes;
    }

    public function exists(string $slug): bool
    {
        return isset($this->all()[$slug]);
    }

    public function find(string $slug): ?ThemeManifest
    {
        return $this->all()[$slug] ?? null;
    }

    public function path(string $slug): ?string
    {
        foreach ($this->paths->searchPaths() as $base) {
            $dir = $base . '/' . $slug;
            if (is_dir($dir) && is_file($dir . '/theme.json')) {
                return $dir;
            }
        }
        return null;
    }

    // ---------- ativação ----------

    public function active(): ?string
    {
        $state = $this->readState();
        return $state['active'] ?? null;
    }

    public function activeManifest(): ?ThemeManifest
    {
        $slug = $this->active();
        return $slug !== null ? $this->find($slug) : null;
    }

    public function activate(string $slug): void
    {
        if (!$this->exists($slug)) {
            throw new \RuntimeException("Tema não encontrado: $slug");
        }

        $this->writeState(['version' => 1, 'active' => $slug]);
        self::$cache = null;
    }

    public function deactivate(): void
    {
        $this->writeState(['version' => 1, 'active' => null]);
        self::$cache = null;
    }

    // ---------- paths de conteúdo ----------

    public function activeBase(): ?string
    {
        $slug = $this->active();
        return $slug !== null ? $this->path($slug) : null;
    }

    public function viewsBase(): ?string
    {
        $base = $this->activeBase();
        if ($base === null) {
            return null;
        }
        $dir = $base . '/resources/views';
        return is_dir($dir) ? $dir : null;
    }

    public function assetsBase(string $kind): ?string
    {
        $base = $this->activeBase();
        if ($base !== null && is_dir($base . '/resources/ui/' . $kind)) {
            return $base . '/resources/ui/' . $kind;
        }

        $skeleton = $this->paths->skeleton() . '/ui/' . $kind;
        return is_dir($skeleton) ? $skeleton : null;
    }

    // ---------- estado ----------

    private function readState(): array
    {
        $file = $this->paths->storage();

        if (!is_file($file)) {
            return ['version' => 1, 'active' => null];
        }

        $data = json_decode((string) file_get_contents($file), true);
        return is_array($data) ? $data : ['version' => 1, 'active' => null];
    }

    private function writeState(array $state): void
    {
        $file = $this->paths->storage();
        $dir  = dirname($file);

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        file_put_contents(
            $file,
            json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
