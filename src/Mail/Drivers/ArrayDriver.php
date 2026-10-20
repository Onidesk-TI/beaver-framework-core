<?php

declare(strict_types=1);

namespace Beaver\Mail\Drivers;

use Beaver\Mail\Contracts\MailDriver;
use Beaver\Mail\Message;

/**
 * Driver em memória — para testes.
 */
class ArrayDriver implements MailDriver
{
    /** @var array<int, Message> */
    private array $sent = [];

    public function send(Message $message): bool
    {
        $this->sent[] = $message;
        return true;
    }

    /** @return array<int, Message> */
    public function sent(): array
    {
        return $this->sent;
    }

    public function count(): int
    {
        return count($this->sent);
    }

    public function last(): ?Message
    {
        return $this->sent[count($this->sent) - 1] ?? null;
    }

    public function flush(): void
    {
        $this->sent = [];
    }
}
