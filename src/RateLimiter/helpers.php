<?php

declare(strict_types=1);

use Beaver\Foundation\Application;
use Beaver\RateLimiter\RateLimiter;

if (!function_exists('rate_limiter')) {
    /**
     * Devolve um RateLimiter configurado.
     *
     *     $limiter = rate_limiter('login', 5, 60);
     */
    function rate_limiter(string $name = 'default', int $maxAttempts = 60, int $decaySeconds = 60): RateLimiter
    {
        $base = Application::getInstance()->make(RateLimiter::class);

        return $base
            ->setLimit($maxAttempts, $decaySeconds);
    }
}
