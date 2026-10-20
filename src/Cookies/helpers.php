<?php

declare(strict_types=1);

use Beaver\Cookies\CookieJar;
use Beaver\Foundation\Application;

if (!function_exists('cookies')) {
    function cookies(): CookieJar
    {
        return Application::getInstance()->make(CookieJar::class);
    }
}

if (!function_exists('cookie_set')) {
    function cookie_set(string $name, string $value, ?int $ttl = null, array $options = []): void
    {
        cookies()->set($name, $value, $ttl, $options);
    }
}

if (!function_exists('cookie_get')) {
    function cookie_get(string $name, ?string $default = null): ?string
    {
        return cookies()->get($name, $default);
    }
}

if (!function_exists('cookie_has')) {
    function cookie_has(string $name): bool
    {
        return cookies()->has($name);
    }
}

if (!function_exists('cookie_forget')) {
    function cookie_forget(string $name, array $options = []): void
    {
        cookies()->forget($name, $options);
    }
}

if (!function_exists('cookie_all')) {
    function cookie_all(): array
    {
        return cookies()->all();
    }
}
