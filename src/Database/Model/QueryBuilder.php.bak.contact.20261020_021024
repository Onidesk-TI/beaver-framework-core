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

use Beaver\Database\Database;

/**
 * Query builder fluente para os Models.
 *
 * Uso:
 *
 *     User::query()
 *         ->where('age', '>', 18)
 *         ->where('is_active', true)
 *         ->orderBy('name', 'asc')
 *         ->limit(10)
 *         ->get();
 *
 *     User::where('email', 'x@y.com')->first();
 *     User::query()->count();
 *
 * Gera SQL ANSI (SQLite, MySQL com sql_mode=ANSI, PostgreSQL).
 */
class QueryBuilder
{
    /** @var class-string<Model> */
    private string $modelClass;

    private string $table;
    private string $primaryKey;

    /** @var array<int, array{boolean:string, column:string, operator:string, value:mixed}> */
    private array $wheres = [];

    /** @var array<int, array{column:string, direction:string}> */
    private array $orders = [];

    private ?int $limit  = null;
    private ?int $offset = null;

    /** @var array<int, string> */
    private array $selects = ['*'];

    public function __construct(string $modelClass)
    {
        if (!is_subclass_of($modelClass, Model::class)) {
            throw new \InvalidArgumentException(
                "QueryBuilder expects a Model subclass, got: {$modelClass}"
            );
        }

        $this->modelClass = $modelClass;
        $this->table      = $modelClass::table();
        $this->primaryKey = $modelClass::primaryKey();
    }

    // ---------- API fluente ----------

    public function where(string $column, mixed $operator, mixed $value = null): self
    {
       // Suporte para where('col', 'val') no estilo Laravel:
       // se vieram só 2 argumentos, o segundo é o valor e o operador é '='.
        if (func_num_args() === 2) {
            $value    = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
        'boolean'  => 'AND',
        'column'   => $column,
        'operator' => strtoupper((string) $operator),
        'value'    => $value,
        ];

        return $this;
    }



    public function orWhere(string $column, mixed $operator, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            $value    = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
        'boolean'  => 'OR',
        'column'   => $column,
        'operator' => strtoupper((string) $operator),
        'value'    => $value,
        ];

        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        $this->wheres[] = [
            'boolean'  => 'AND',
            'column'   => $column,
            'operator' => 'IN',
            'value'    => $values,
        ];

        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->wheres[] = [
            'boolean'  => 'AND',
            'column'   => $column,
            'operator' => 'IS NULL',
            'value'    => null,
        ];

        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->wheres[] = [
            'boolean'  => 'AND',
            'column'   => $column,
            'operator' => 'IS NOT NULL',
            'value'    => null,
        ];

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';

        $this->orders[] = ['column' => $column, 'direction' => $direction];

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function select(string ...$columns): self
    {
        $this->selects = $columns ?: ['*'];
        return $this;
    }

    // ---------- Terminal ----------

    /** @return Model[] */
    public function get(): array
    {
        $sql    = $this->toSql();
        $params = $this->bindings();

        $rows = Database::getInstance()->select($sql, $params);

        return array_map(
            fn (array $row) => $this->modelClass::hydrate($row),
            $rows,
        );
    }

    public function first(): ?Model
    {
        $this->limit(1);
        $rows = $this->get();
        return $rows[0] ?? null;
    }

    public function count(): int
    {
        $original = $this->selects;
        $this->selects = ['COUNT(*) AS __count'];

        $sql    = $this->toSql();
        $params = $this->bindings();

        $row = Database::getInstance()->selectOne($sql, $params);

        $this->selects = $original;

        return (int) ($row['__count'] ?? 0);
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    // ---------- Debug ----------

    public function toSql(): string
    {
        $cols = implode(', ', array_map([$this, 'quoteIdentifier'], $this->selects));

        $sql = sprintf(
            'SELECT %s FROM %s',
            $cols,
            $this->quoteIdentifier($this->table),
        );

        if ($this->wheres !== []) {
            $sql .= ' WHERE ' . $this->compileWheres();
        }

        if ($this->orders !== []) {
            $orders = array_map(
                fn ($o) => $this->quoteIdentifier($o['column']) . ' ' . $o['direction'],
                $this->orders,
            );
            $sql .= ' ORDER BY ' . implode(', ', $orders);
        }

        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }

        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . $this->offset;
        }

        return $sql;
    }

    /** @return array<string, mixed> */
    public function bindings(): array
    {
        $bindings = [];
        $i = 0;

        foreach ($this->wheres as $w) {
            $op = $w['operator'];

            if ($op === 'IS NULL' || $op === 'IS NOT NULL') {
                continue;
            }

            if ($op === 'IN') {
                foreach ((array) $w['value'] as $v) {
                    $bindings[':w' . ($i++)] = $v;
                }
                continue;
            }

            $bindings[':w' . ($i++)] = $w['value'];
        }

        return $bindings;
    }

    // ---------- Internos ----------

    private function compileWheres(): string
    {
        $parts = [];
        $i = 0;

        foreach ($this->wheres as $idx => $w) {
            $col = $this->quoteIdentifier($w['column']);
            $op  = $w['operator'];

            if ($op === 'IS NULL' || $op === 'IS NOT NULL') {
                $fragment = "{$col} {$op}";
            } elseif ($op === 'IN') {
                $count = count((array) $w['value']);
                if ($count === 0) {
                    $fragment = '1 = 0';
                } else {
                    $placeholders = [];
                    for ($k = 0; $k < $count; $k++) {
                        $placeholders[] = ':w' . ($i++);
                    }
                    $fragment = "{$col} IN (" . implode(', ', $placeholders) . ')';
                }
            } else {
                $placeholder = ':w' . ($i++);
                $fragment = "{$col} {$op} {$placeholder}";
            }

            $parts[] = $idx === 0 ? $fragment : ($w['boolean'] . ' ' . $fragment);
        }

        return implode(' ', $parts);
    }

    private function quoteIdentifier(string $name): string
    {
        if ($name === '*') {
            return '*';
        }

        if (preg_match('/\s/', $name) || str_contains($name, '(')) {
            return $name;
        }

        if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            throw new \InvalidArgumentException("Invalid identifier: {$name}");
        }

        return '"' . $name . '"';
    }
}
