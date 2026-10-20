<?php

declare(strict_types=1);

use Beaver\Encryption\Encrypter;
use Beaver\Foundation\Application;

if (!function_exists('encrypter')) {
    function encrypter(): Encrypter
    {
        return Application::getInstance()->make(Encrypter::class);
    }
}

if (!function_exists('encrypt_value')) {
    function encrypt_value(mixed $value): string
    {
        return encrypter()->encrypt($value);
    }
}

if (!function_exists('decrypt_value')) {
    function decrypt_value(string $payload): mixed
    {
        return encrypter()->decrypt($payload);
    }
}
