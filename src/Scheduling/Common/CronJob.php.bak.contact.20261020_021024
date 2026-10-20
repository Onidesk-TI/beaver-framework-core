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

namespace Beaver\Scheduling\Common;

/**
 * Value object que representa um job agendado no sistema.
 *
 * É igual em todos os SO. Cada driver (Linux, Windows, macOS)
 * traduz este objeto para o formato nativo de cada um.
 */
final class CronJob
{
    public function __construct(
        public readonly string $name,
        public readonly string $expression,
        public readonly string $command,
        public readonly bool $enabled = true,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name'       => $this->name,
            'expression' => $this->expression,
            'command'    => $this->command,
            'enabled'    => $this->enabled,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name:       $data['name'],
            expression: $data['expression'],
            command:    $data['command'],
            enabled:    $data['enabled'] ?? true,
        );
    }
}
