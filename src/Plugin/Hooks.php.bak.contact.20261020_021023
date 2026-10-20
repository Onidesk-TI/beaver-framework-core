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
namespace Beaver\Plugin;

class Hooks
{
    /** @var array<string, array<int, array{callback:callable, priority:int}>> */
    private array $listeners = [];

    public function on(string $event, callable $callback, int $priority = 10): void
    {
        $this->listeners[$event][] = [
            'callback' => $callback,
            'priority' => $priority,
        ];
        usort($this->listeners[$event], fn($a, $b) => $a['priority'] <=> $b['priority']);
    }

    public function emit(string $event, mixed ...$args): array
    {
        $results = [];
        foreach ($this->listeners[$event] ?? [] as $listener) {
            $results[] = ($listener['callback'])(...$args);
        }
        return $results;
    }

    public function filter(string $event, mixed $value, mixed ...$args): mixed
    {
        foreach ($this->listeners[$event] ?? [] as $listener) {
            $value = ($listener['callback'])($value, ...$args);
        }
        return $value;
    }

    public function has(string $event): bool
    {
        return !empty($this->listeners[$event]);
    }

    public function listeners(): array
    {
        return $this->listeners;
    }
}
