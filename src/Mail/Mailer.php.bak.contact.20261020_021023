<?php

/**
 * Beaver Framework — Modern PHP framework with a plugin ecosystem.
 *
 * @package    Beaver Framework
 * @author     Franco <onidesk@outlook.com>
 * @copyright  2026 Onidesk
 * @license    MIT <https://opensource.org/licenses/MIT>
 * @link       https://github.com/Onidesk-TI/beaver-framework
 *
 * ──────────────────────────────────────────────────────────────
 * API HÍBRIDA — três formas de usar
 * ──────────────────────────────────────────────────────────────
 *
 *   1) Helper global (uso comum)
 *         mail_send('user@example.com', 'Assunto', 'Corpo');
 *
 *   2) Container (controlo)
 *         $mailer = app()->make(Mailer::class);
 *         $mailer->send('user@example.com', 'Assunto', 'Corpo', html: true);
 *
 *   3) Injeção de dependências
 *         public function __construct(private Mailer $mailer) {}
 */

namespace Beaver\Mail;

use Beaver\Mail\Contracts\MailDriver;

class Mailer
{
    public function __construct(
        private MailDriver $driver,
        private string $fromAddress = '',
        private string $fromName = '',
    ) {
    }

    /**
     * Envia uma mensagem.
     *
     * @param string|array<int, string> $to
     */
    public function send(
        string|array $to,
        string $subject,
        string $body,
        bool $html = false,
    ): bool {
        $message = new Message(
            to:          is_array($to) ? $to : [$to],
            subject:     $subject,
            body:        $body,
            html:        $html,
            fromAddress: $this->fromAddress,
            fromName:    $this->fromName,
        );

        return $this->driver->send($message);
    }

    /**
     * Altera o remetente.
     */
    public function from(string $address, string $name = ''): self
    {
        $this->fromAddress = $address;
        $this->fromName    = $name;

        return $this;
    }

    /**
     * Devolve o driver atual.
     */
    public function driver(): MailDriver
    {
        return $this->driver;
    }
}
