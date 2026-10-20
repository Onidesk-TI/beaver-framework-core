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
 * Driver em memória — útil para testes (não persiste).
 */
class ArrayDriver implements SessionDriver
{
    /** @var array<string, mixed> */
    private array $data = [];

    private string $sessionId = 'test-session';

    public function start(): void
    {
        // Nada a fazer
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->data;
    }

    /** @param array<string, mixed> $data */
    public function replace(array $data): void
    {
        $this->data = $data;
    }

    public function id(): string
    {
        return $this->sessionId;
    }

    public function regenerate(): void
    {
        $this->sessionId = 'test-session-' . bin2hex(random_bytes(4));
    }

    public function destroy(): void
    {
        $this->data = [];
    }
}
