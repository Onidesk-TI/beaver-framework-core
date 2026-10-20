<?php

declare(strict_types=1);

namespace Beaver\Auth;

use Beaver\Auth\Contracts\AuthDriver;

final class Auth
{
    public static function register(AuthDriver $driver): void
    {
        AuthManager::setDriver($driver);
    }

    public static function check(): bool
    {
        return AuthManager::check();
    }
    public static function guest(): bool
    {
        return !AuthManager::check();
    }
    public static function user(): ?object
    {
        return AuthManager::user();
    }
    public static function userOrFail(): object
    {
        return AuthManager::userOrFail();
    }
    public static function id(): int|string|null
    {
        return AuthManager::id();
    }
    public static function attempt(array $c): bool
    {
        return AuthManager::attempt($c);
    }
    public static function logout(): void
    {
        AuthManager::logout();
    }
}
