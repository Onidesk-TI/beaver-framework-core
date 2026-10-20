<?php

declare(strict_types=1);

use Beaver\Foundation\Application;
use Beaver\Logging\Logger;

if (!function_exists('logger')) {
    function logger(): Logger
    {
        return Application::getInstance()->make(Logger::class);
    }
}

if (!function_exists('log_debug')) {
    function log_debug(string $message, array $context = []): void
    {
        logger()->debug($message, $context);
    }
}

if (!function_exists('log_info')) {
    function log_info(string $message, array $context = []): void
    {
        logger()->info($message, $context);
    }
}

if (!function_exists('log_warning')) {
    function log_warning(string $message, array $context = []): void
    {
        logger()->warning($message, $context);
    }
}

if (!function_exists('log_error')) {
    function log_error(string $message, array $context = []): void
    {
        logger()->error($message, $context);
    }
}

if (!function_exists('log_critical')) {
    function log_critical(string $message, array $context = []): void
    {
        logger()->critical($message, $context);
    }
}
