<?php

/**
 * Beaver Framework — Modern PHP framework with a plugin ecosystem.
 *
 * @package    Beaver Framework
 * @author     Franco <onidesk@outlook.com>
 * @copyright  2026 Onidesk
 * @license    MIT <https://opensource.org/licenses/MIT>
 * @link       https://github.com/Onidesk-TI/beaver-framework
 */

namespace Beaver\Session\Drivers;

/**
 * Driver baseado em $_SESSION (sessões nativas do PHP).
 */
class NativeDriver implements SessionDriver
{
    private bool $started = false;

    public function start(): void
    {
        if ($this->started) {
            return;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        $this->started = true;
    }

    /**
     * Devolve todos os dados da sessão.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $this->start();
        return $_SESSION ?? [];
    }

    /**
     * Substitui os dados da sessão.
     *
     * @param array<string, mixed> $data
     */
    public function replace(array $data): void
    {
        $this->start();
        $_SESSION = $data;
    }

    public function id(): string
    {
        $this->start();
        return session_id() ?: '';
    }

    public function regenerate(): void
    {
        $this->start();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            @session_destroy();
        }
        $this->started = false;
    }
}
