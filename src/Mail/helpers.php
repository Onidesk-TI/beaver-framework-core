<?php

declare(strict_types=1);

use Beaver\Foundation\Application;
use Beaver\Mail\Mailer;

if (!function_exists('mailer')) {
    function mailer(): Mailer
    {
        return Application::getInstance()->make(Mailer::class);
    }
}

if (!function_exists('mail_send')) {
    /**
     * @param string|array<int, string> $to
     */
    function mail_send(string|array $to, string $subject, string $body, bool $html = false): bool
    {
        return mailer()->send($to, $subject, $body, $html);
    }
}

if (!function_exists('mail_from')) {
    function mail_from(string $address, string $name = ''): Mailer
    {
        return mailer()->from($address, $name);
    }
}
