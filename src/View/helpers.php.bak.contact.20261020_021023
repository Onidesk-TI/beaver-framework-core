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

use Beaver\Foundation\Application;
use Beaver\View\View;

if (!function_exists('app')) {
    function app(): Application
    {
        return Application::getInstance();
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('view')) {
    function view(string $name, array $data = []): string
    {
        return app()->make(View::class)->render($name, $data);
    }
}

if (!function_exists('config')) {
    function config(?string $key = null, mixed $default = null): mixed
    {
        return app()->config($key, $default);
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $base = app()->basePath;
        return rtrim($base, '/') . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('csrf_token')) {
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
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}



if (!function_exists('assets')) {
    function assets(): \Beaver\View\Assets
    {
        return new class extends \Beaver\View\Assets {
        };
    }
}

if (!function_exists('asset_css')) {
    function asset_css(string $url): void
    {
        \Beaver\View\Assets::css($url);
    }
}

if (!function_exists('asset_js')) {
    function asset_js(string $url, bool $defer = true): void
    {
        \Beaver\View\Assets::js($url, $defer);
    }
}
