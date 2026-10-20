<?php

declare(strict_types=1);

namespace Beaver\Mail;

use Beaver\Mail\Contracts\MailDriver;
use Beaver\Mail\Drivers\ArrayDriver;
use Beaver\Mail\Drivers\LogDriver;
use Beaver\Mail\Drivers\SmtpDriver;

class MailFactory
{
    public static function fromConfig(array $config): Mailer
    {
        $driver = $config['driver'] ?? 'log';

        $mailDriver = match ($driver) {
            'smtp' => new SmtpDriver(
                host:       $config['host'] ?? 'localhost',
                port:       (int) ($config['port'] ?? 587),
                username:   $config['username'] ?? '',
                password:   $config['password'] ?? '',
                encryption: $config['encryption'] ?? 'tls',
                timeout:    (int) ($config['timeout'] ?? 15),
            ),
            'log'   => new LogDriver($config['log_path'] ?? dirname(__DIR__, 2) . '/storage/logs'),
            'array' => new ArrayDriver(),
            default => throw new \InvalidArgumentException("Unknown mail driver: {$driver}"),
        };

        return new Mailer(
            driver:      $mailDriver,
            fromAddress: $config['from']['address'] ?? '',
            fromName:    $config['from']['name'] ?? '',
        );
    }
}
