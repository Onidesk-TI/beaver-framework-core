<?php

/**
 * Beaver Framework — Modern PHP framework with a plugin ecosystem.
 *
 * @package    Beaver Framework
 * @author     Franco <onidesk@outlook.com>
 * @copyright  2026 Onidesk
 * @license    MIT <https://opensource.org/licenses/MIT>
 * @link       https://github.com/Onidesk-TI/beaver-framework
 *
 * ──────────────────────────────────────────────────────────────
 * API HÍBRIDA — helpers globais
 * ──────────────────────────────────────────────────────────────
 */

use Beaver\Foundation\Application;
use Beaver\Session\Session;

if (!function_exists('session')) {
    /**
     * Devolve a instância Session (do container).
     */
    function session(): Session
    {
        return Application::getInstance()->make(Session::class);
    }
}

if (!function_exists('session_put')) {
    function session_put(string $key, mixed $value): void
    {
        session()->put($key, $value);
    }
}

if (!function_exists('session_get')) {
    function session_get(string $key, mixed $default = null): mixed
    {
        return session()->get($key, $default);
    }
}

if (!function_exists('session_has')) {
    function session_has(string $key): bool
    {
        return session()->has($key);
    }
}

if (!function_exists('session_forget')) {
    function session_forget(string $key): void
    {
        session()->forget($key);
    }
}

if (!function_exists('session_pull')) {
    function session_pull(string $key, mixed $default = null): mixed
    {
        return session()->pull($key, $default);
    }
}

if (!function_exists('session_flash')) {
    function session_flash(string $key, mixed $value): void
    {
        session()->flash($key, $value);
    }
}

if (!function_exists('session_pull_flash')) {
    function session_pull_flash(string $key, mixed $default = null): mixed
    {
        return session()->pullFlash($key, $default);
    }
}

if (!function_exists('session_flush')) {
    function session_flush(): void
    {
        session()->flush();
    }
}

if (!function_exists('session_regenerate')) {
    function session_regenerate(): void
    {
        session()->regenerate();
    }
}

if (!function_exists('session_destroy')) {
    function session_destroy(): void
    {
        session()->destroy();
    }
}
