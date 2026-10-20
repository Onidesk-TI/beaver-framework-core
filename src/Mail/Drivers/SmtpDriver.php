<?php

declare(strict_types=1);

namespace Beaver\Mail\Drivers;

use Beaver\Mail\Contracts\MailDriver;
use Beaver\Mail\Message;

/**
 * Driver SMTP com suporte a TLS (porta 587) e SSL (porta 465).
 *
 * Zero dependências externas — usa fsockopen nativo.
 */
class SmtpDriver implements MailDriver
{
    public function __construct(
        private string $host,
        private int $port = 587,
        private string $username = '',
        private string $password = '',
        private string $encryption = 'tls', // 'tls' | 'ssl' | ''
        private int $timeout = 15,
    ) {
    }

    public function send(Message $message): bool
    {
        $remote = ($this->encryption === 'ssl' ? 'ssl://' : '')
            . $this->host . ':' . $this->port;

        $socket = @stream_socket_client(
            $remote,
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
        );

        if ($socket === false) {
            throw new \RuntimeException(
                "SMTP connection failed: {$errstr} ({$errno})"
            );
        }

        stream_set_timeout($socket, $this->timeout);

        try {
            $this->expect($socket, 220);
            $this->cmd($socket, 'EHLO ' . gethostname(), 250);

            if ($this->encryption === 'tls') {
                $this->cmd($socket, 'STARTTLS', 220);
                if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('TLS handshake failed');
                }
                $this->cmd($socket, 'EHLO ' . gethostname(), 250);
            }

            if ($this->username !== '') {
                $this->cmd($socket, 'AUTH LOGIN', 334);
                $this->cmd($socket, base64_encode($this->username), 334);
                $this->cmd($socket, base64_encode($this->password), 235);
            }

            $from = $message->fromAddress;
            $this->cmd($socket, "MAIL FROM:<{$from}>", 250);

            foreach ($message->to as $to) {
                $this->cmd($socket, "RCPT TO:<{$to}>", [250, 251]);
            }
            foreach ($message->cc as $cc) {
                $this->cmd($socket, "RCPT TO:<{$cc}>", [250, 251]);
            }
            foreach ($message->bcc as $bcc) {
                $this->cmd($socket, "RCPT TO:<{$bcc}>", [250, 251]);
            }

            $this->cmd($socket, 'DATA', 354);

            $data = $this->buildData($message);
            $this->cmd($socket, $data . "\r\n.", 250);

            $this->cmd($socket, 'QUIT', 221);
        } finally {
            @fclose($socket);
        }

        return true;
    }

    private function buildData(Message $message): string
    {
        $headers = [];

        $headers[] = 'From: ' . $message->formatFrom();
        $headers[] = 'To: ' . implode(', ', $message->to);

        if ($message->cc !== []) {
            $headers[] = 'Cc: ' . implode(', ', $message->cc);
        }

        if ($message->replyTo !== []) {
            $headers[] = 'Reply-To: ' . implode(', ', $message->replyTo);
        }

        $headers[] = 'Subject: ' . $message->encodedSubject();
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . gethostname() . '>';

        $ctype = $message->html
            ? 'text/html; charset=UTF-8'
            : 'text/plain; charset=UTF-8';

        $headers[] = 'Content-Type: ' . $ctype;
        $headers[] = 'Content-Transfer-Encoding: base64';

        $body = chunk_split(base64_encode($message->body), 76, "\r\n");

        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
    }

    /**
     * Envia um comando e valida a resposta.
     *
     * @param int|array<int, int> $expect
     */
    private function cmd($socket, string $command, int|array $expect): string
    {
        fwrite($socket, $command . "\r\n");

        return $this->expect($socket, $expect);
    }

    /**
     * Lê a resposta e valida o código.
     *
     * @param int|array<int, int> $expect
     */
    private function expect($socket, int|array $expect): string
    {
        $response = '';
        while (($line = fgets($socket, 512)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $code = (int) substr($response, 0, 3);
        $valid = is_array($expect) ? in_array($code, $expect, true) : $code === $expect;

        if (!$valid) {
            throw new \RuntimeException(
                "SMTP error: expected " . (is_array($expect) ? implode('/', $expect) : $expect)
                . ", got {$code} — " . trim($response)
            );
        }

        return $response;
    }
}
