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

/**
 * Foundation helpers.
 *
 * Global functions available throughout the framework and the project.
 * Loaded once from Foundation\Application::__construct() after Env::load().
 */

if (!function_exists('env')) {
    /**
     * Read an environment variable, with a default and light type coercion.
     *
     *   env('EZ4U_KEY')                 → string|null
     *   env('APP_DEBUG', false)         → bool
     *   env('APP_NAME', 'Beaver')       → string
     *
     * "true"/"false"/"null"/"empty" (with or without parentheses)
     * are converted to their native PHP types.
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        return match (strtolower($value)) {
            'true',  '(true)'  => true,
            'false', '(false)' => false,
            'null',  '(null)'  => null,
            'empty', '(empty)' => '',
            default            => $value,
        };
    }
}

if (!function_exists('base_path')) {
    /**
     * Absolute path to the project root (where the app is installed).
     * Optional $path is appended.
     */
    function base_path(string $path = ''): string
    {
        $base = \Beaver\Foundation\Application::getInstance()->basePath;
        return $path === '' ? $base : $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('storage_path')) {
    /**
     * Absolute path to the project's storage directory.
     */
    function storage_path(string $path = ''): string
    {
        $base = base_path('storage');
        return $path === '' ? $base : $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('config')) {
    /**
     * Read a config value using dot notation: config('app.timezone', 'UTC').
     */
    function config(?string $key = null, mixed $default = null): mixed
    {
        return \Beaver\Foundation\Application::getInstance()
            ->config($key, $default);
    }
}


if (!function_exists('beaver_version')) {
    /**
     * Versão do framework.
     * Lê de Beaver\Foundation\Version (que por sua vez lê composer.json).
     */
    function beaver_version(): string
    {
        return \Beaver\Foundation\Version::number();
    }

    /**
     * Versão da Plugin API / SDK.
     * Lê de Beaver\Foundation\Version::api() (composer.json → extra.beaver.api_version).
     */
    function beaver_api_version(): string
    {
        return \Beaver\Foundation\Version::api();
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Devolve o token CSRF atual (cria um se não existir).
     *
     * @return string
     */
    function csrf_token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf'];
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Devolve um <input type="hidden"> com o token CSRF.
     */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('csrf_verify')) {
    /**
     * Verifica se o token recebido é válido.
     */
    function csrf_verify(?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        $expected = $_SESSION['_csrf'] ?? null;

        return is_string($expected) && hash_equals($expected, $token);
    }
}
