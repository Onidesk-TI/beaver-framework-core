<?php

declare(strict_types=1);

namespace Beaver\Auth;

use Beaver\Auth\Contracts\AuthDriver;
use Beaver\Auth\Exceptions\UnauthenticatedException;

final class AuthManager
{
    private static ?AuthDriver $driver = null;

    public static function setDriver(AuthDriver $driver): void
    {
        self::$driver = $driver;
    }

    public static function hasDriver(): bool
    {
        return self::$driver !== null;
    }

    public static function driver(): AuthDriver
    {
        if (self::$driver === null) {
            throw new \RuntimeException('Auth driver não registado.');
        }
        return self::$driver;
    }

    public static function check(): bool
    {
        return self::$driver?->check() ?? false;
    }

    public static function user(): ?object
    {
        return self::$driver?->user();
    }

    public static function id(): int|string|null
    {
        return self::$driver?->id();
    }

    public static function attempt(array $credentials): bool
    {
        return self::$driver?->attempt($credentials) ?? false;
    }

    public static function logout(): void
    {
        self::$driver?->logout();
    }

    public static function userOrFail(): object
    {
        $user = self::$driver?->user();
        if ($user === null) {
            throw new UnauthenticatedException('Nenhum utilizador autenticado.');
        }
        return $user;
    }
}
