<?php

declare(strict_types=1);

namespace Beaver\Mail\Drivers;

use Beaver\Mail\Contracts\MailDriver;
use Beaver\Mail\Message;

/**
 * Driver que escreve o email num ficheiro de log.
 * Útil em desenvolvimento (não envia nada).
 */
class LogDriver implements MailDriver
{
    public function __construct(
        private string $path,
    ) {
        if (!is_dir($this->path)) {
            @mkdir($this->path, 0755, true);
        }
    }

    public function send(Message $message): bool
    {
        $file = $this->path . '/mail-' . date('Y-m-d') . '.log';

        $entry = str_repeat('=', 70) . PHP_EOL;
        $entry .= '[' . date('Y-m-d H:i:s') . ']' . PHP_EOL;
        $entry .= 'To:      ' . implode(', ', $message->to) . PHP_EOL;
        $entry .= 'From:    ' . $message->formatFrom() . PHP_EOL;
        $entry .= 'Subject: ' . $message->subject . PHP_EOL;
        $entry .= 'HTML:    ' . ($message->html ? 'yes' : 'no') . PHP_EOL;
        $entry .= str_repeat('-', 70) . PHP_EOL;
        $entry .= $message->body . PHP_EOL;
        $entry .= str_repeat('=', 70) . PHP_EOL . PHP_EOL;

        return @file_put_contents($file, $entry, FILE_APPEND | LOCK_EX) !== false;
    }
}
