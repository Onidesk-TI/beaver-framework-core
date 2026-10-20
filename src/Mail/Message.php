<?php

declare(strict_types=1);

namespace Beaver\Mail;

final class Message
{
    /**
     * @param array<int, string> $to
     * @param array<int, string> $cc
     * @param array<int, string> $bcc
     */
    public function __construct(
        public readonly array $to,
        public readonly string $subject,
        public readonly string $body,
        public readonly bool $html = false,
        public readonly string $fromAddress = '',
        public readonly string $fromName = '',
        public readonly array $cc = [],
        public readonly array $bcc = [],
        public readonly array $replyTo = [],
    ) {
    }

    /**
     * Assunto codificado para UTF-8 em headers SMTP.
     */
    public function encodedSubject(): string
    {
        if (!preg_match('/[^\x20-\x7E]/', $this->subject)) {
            return $this->subject;
        }

        return '=?UTF-8?B?' . base64_encode($this->subject) . '?=';
    }

    /**
     * Formato "Nome <email>".
     */
    public function formatFrom(): string
    {
        if ($this->fromName === '') {
            return $this->fromAddress;
        }

        return sprintf(
            '%s <%s>',
            '=?UTF-8?B?' . base64_encode($this->fromName) . '?=',
            $this->fromAddress
        );
    }
}
