<?php

declare(strict_types=1);

namespace Beaver\Mail\Contracts;

use Beaver\Mail\Message;

interface MailDriver
{
    /**
     * Envia uma mensagem.
     */
    public function send(Message $message): bool;
}
