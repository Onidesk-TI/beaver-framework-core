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

namespace Beaver\Database\Model;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Coleção simples de Models (e valores arbitrários).
 *
 * @template T
 * @implements ArrayAccess<int, T>
 * @implements IteratorAggregate<int, T>
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate
{
    /** @var array<int, mixed> */
    protected array $items = [];

    /** @param array<int, mixed> $items */
    public function __construct(array $items = [])
    {
        $this->items = array_values($items);
    }

    // ---------- Acesso ----------

    /** @return array<int, mixed> */
    public function all(): array
    {
        return $this->items;
    }

    public function first(): mixed
    {
        return $this->items[0] ?? null;
    }

    public function last(): mixed
    {
        return $this->items === [] ? null : $this->items[array_key_last($this->items)];
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function isNotEmpty(): bool
    {
        return $this->items !== [];
    }

    public function count(): int
    {
        return count($this->items);
    }

    // ---------- Transformações ----------

    /**
     * Extrai uma coluna/atributo de todos os items.
     */
    public function pluck(string $value, ?string $key = null): static
    {
        $out = [];

        foreach ($this->items as $item) {
            $v = $this->extract($item, $value);

            if ($key === null) {
                $out[] = $v;
            } else {
                $out[$this->extract($item, $key)] = $v;
            }
        }

        return new static($out);
    }

    /**
     * Filtra por callback.
     *
     * @param callable(mixed, int): bool $callback
     */
    public function filter(callable $callback): static
    {
        return new static(array_values(
            array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH)
        ));
    }

    /**
     * Mapeia cada item.
     *
     * @param callable(mixed, int): mixed $callback
     */
    public function map(callable $callback): static
    {
        $out = [];
        foreach ($this->items as $i => $item) {
            $out[] = $callback($item, $i);
        }
        return new static($out);
    }

    public function toArray(): array
    {
        return array_map(
            fn ($item) => $item instanceof Model ? $item->toArray() : $item,
            $this->items,
        );
    }

    // ---------- ArrayAccess ----------

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    // ---------- Iterator ----------

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    // ---------- Internos ----------

    /** Extrai um valor de um Model ou array. */
    protected function extract(mixed $item, string $key): mixed
    {
        if ($item instanceof Model) {
            return $item->{$key};
        }

        if (is_array($item)) {
            return $item[$key] ?? null;
        }

        if (is_object($item)) {
            return $item->{$key} ?? null;
        }

        return null;
    }
}
