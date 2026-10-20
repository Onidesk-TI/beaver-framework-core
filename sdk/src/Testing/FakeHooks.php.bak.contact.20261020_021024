<?php

declare(strict_types=1);

/**
 * Beaver Framework — Modern PHP framework with a plugin ecosystem.
 *
 * @package    Beaver SDK
 * @version    0.1.0
 * @author     Franco <onidesk@outlook.com>
 * @copyright  2026 Onidesk
 * @license    MIT <https://opensource.org/licenses/MIT>
 * @link       https://github.com/Onidesk-TI/beaver-framework
 */

namespace Beaver\Sdk\Testing;

use Beaver\Plugin\Hooks;

final class FakeHooks extends Hooks
{
    /** @var array<int,array{event:string,args:array}> */
    private array $emitted = [];

    public function emit(string $event, mixed ...$args): array
    {
        $this->emitted[] = ['event' => $event, 'args' => $args];
        return parent::emit($event, ...$args);
    }

    public function emitted(string $event): array
    {
        return array_values(array_filter(
            $this->emitted,
            fn ($e) => $e['event'] === $event
        ));
    }

    public function wasEmitted(string $event): bool
    {
        return $this->emitted($event) !== [];
    }

    public function allEmitted(): array
    {
        return $this->emitted;
    }

    public function reset(): void
    {
        $this->emitted = [];
    }
}
