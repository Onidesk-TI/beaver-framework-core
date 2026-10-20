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

namespace Beaver\Database\Migrations;

class Blueprint
{
    private string $table;
    private array $columns = [];
    private array $indexes = [];
    private array $foreignKeys = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function id(string $name = 'id'): self
    {
        $this->columns[] = ['name' => $name, 'type' => 'id', 'nullable' => false, 'default' => null];
        return $this;
    }

    public function string(string $name, int $length = 255): self
    {
        $this->columns[] = ['name' => $name, 'type' => 'string',
         'length' => $length, 'nullable' => false, 'default' => null];
        return $this;
    }

    public function text(string $name): self
    {
        $this->columns[] = ['name' => $name, 'type' => 'text', 'nullable' => false, 'default' => null];
        return $this;
    }

    public function integer(string $name): self
    {
        $this->columns[] = ['name' => $name, 'type' => 'integer', 'nullable' => false, 'default' => null];
        return $this;
    }

    /**
     * Número decimal (para dinheiro).
     *   MySQL:      DECIMAL(10,2)
     *   PostgreSQL: NUMERIC(10,2)
     *   SQLite:     NUMERIC
     */
    public function decimal(string $name, int $precision = 10, int $scale = 2): self
    {
        $this->columns[] = [
            'name'      => $name,
            'type'      => 'decimal',
            'precision' => $precision,
            'scale'     => $scale,
            'nullable'  => false,
            'default'   => null,
        ];
        return $this;
    }

        /**
     * Data (sem hora).
     *   MySQL:      DATE
     *   PostgreSQL: DATE
     *   SQLite:     TEXT
     */
    public function date(string $name): self
    {
        $this->columns[] = ['name' => $name, 'type' => 'date', 'nullable' => false, 'default' => null];
        return $this;
    }

    /**
     * Hora (sem data).
     *   MySQL:      TIME
     *   PostgreSQL: TIME
     *   SQLite:     TEXT
     */
    public function time(string $name): self
    {
        $this->columns[] = ['name' => $name, 'type' => 'time', 'nullable' => false, 'default' => null];
        return $this;
    }

    /**
     * JSON.
     *   MySQL:      JSON
     *   PostgreSQL: JSONB
     *   SQLite:     TEXT
     */
    public function json(string $name): self
    {
        $this->columns[] = ['name' => $name, 'type' => 'json', 'nullable' => false, 'default' => null];
        return $this;
    }

    /**
     * UUID (36 caracteres).
     *   MySQL:      CHAR(36)
     *   PostgreSQL: UUID
     *   SQLite:     TEXT
     */
    public function uuid(string $name): self
    {
        $this->columns[] = ['name' => $name, 'type' => 'uuid', 'nullable' => false, 'default' => null];
        return $this;
    }

    /**
     * Foreign ID — chave estrangeira.
     * Devolve um objeto que permite configurar a referência.
     *
     * Uso:
     *   $t->foreignId('user_id')->references('users');
     *   $t->foreignId('user_id')->references('users', 'uuid');
     */
    public function foreignId(string $name): ForeignKeyDefinition
    {
        $this->columns[] = [
            'name'      => $name,
            'type'      => 'foreignId',
            'nullable'  => false,
            'default'   => null,
        ];

        return new ForeignKeyDefinition($this, $name);
    }

    /**
     * Registar uma foreign key (chamada pelo ForeignKeyDefinition).
     */
    public function addForeignKey(string $column, string $refTable, string $refColumn = 'id'): void
    {
        $this->foreignKeys[] = [
            'column'    => $column,
            'refTable'  => $refTable,
            'refColumn' => $refColumn,
        ];
    }

    public function boolean(string $name, bool $default = false): self
    {
        $this->columns[] = ['name' => $name, 'type' => 'boolean', 'nullable' => false, 'default' => $default];
        return $this;
    }

    public function datetime(string $name): self
    {
        $this->columns[] = ['name' => $name, 'type' => 'datetime', 'nullable' => false, 'default' => null];
        return $this;
    }

    public function timestamps(): self
    {
        $this->columns[] = ['name' => 'created_at', 'type' => 'datetime', 'nullable' => true, 'default' => null];
        $this->columns[] = ['name' => 'updated_at', 'type' => 'datetime', 'nullable' => true, 'default' => null];
        return $this;
    }

    public function nullable(): self
    {
        $idx = count($this->columns) - 1;
        if ($idx >= 0) {
            $this->columns[$idx]['nullable'] = true;
        }
        return $this;
    }

    public function default(mixed $value): self
    {
        $idx = count($this->columns) - 1;
        if ($idx >= 0) {
            $this->columns[$idx]['default'] = $value;
        }
        return $this;
    }

    public function unique(string $column, ?string $indexName = null): self
    {
        $this->indexes[] = [
            'type'    => 'unique',
            'columns' => [$column],
            'name'    => $indexName ?? $this->table . '_' . $column . '_unique',
        ];
        return $this;
    }

    public function index(string $column, ?string $indexName = null): self
    {
        $this->indexes[] = [
            'type'    => 'index',
            'columns' => [$column],
            'name'    => $indexName ?? $this->table . '_' . $column . '_index',
        ];
        return $this;
    }

    public function toCreateSql(string $driver): string
    {
        $parts = [];

        foreach ($this->columns as $col) {
            $parts[] = $this->compileColumn($col, $driver);
        }

        foreach ($this->indexes as $idx) {
            if ($idx['type'] === 'unique') {
                $cols = implode(', ', array_map([$this, 'quote'], $idx['columns']));
                $parts[] = "UNIQUE ({$cols})";
            }
        }

        foreach ($this->foreignKeys as $fk) {
            $parts[] = sprintf(
                'FOREIGN KEY (%s) REFERENCES %s (%s)',
                $this->quote($fk['column']),
                $this->quote($fk['refTable']),
                $this->quote($fk['refColumn']),
            );
        }

        $columnsSql = implode(",\n    ", $parts);

        return sprintf(
            "CREATE TABLE %s (\n    %s\n)",
            $this->quote($this->table),
            $columnsSql,
        );
    }

    public function toIndexSql(string $driver): array
    {
        $out = [];
        foreach ($this->indexes as $idx) {
            if ($idx['type'] !== 'index') {
                continue;
            }
            $cols = implode(', ', array_map([$this, 'quote'], $idx['columns']));
            $out[] = sprintf(
                'CREATE INDEX %s ON %s (%s)',
                $this->quote($idx['name']),
                $this->quote($this->table),
                $cols,
            );
        }
        return $out;
    }

    private function compileColumn(array $col, string $driver): string
    {
        $name = $this->quote($col['name']);
        $type = $this->compileType($col, $driver);

        $sql = "{$name} {$type}";

        if (empty($col['nullable'])) {
            $sql .= ' NOT NULL';
        }

        if (array_key_exists('default', $col) && $col['default'] !== null) {
            $sql .= ' DEFAULT ' . $this->compileDefault($col['default'], $col['type'], $driver);
        }

        return $sql;
    }

    private function compileType(array $col, string $driver): string
    {
        return match ($col['type']) {
            'id' => match ($driver) {
                'mysql'  => 'INT AUTO_INCREMENT PRIMARY KEY',
                'pgsql'  => 'SERIAL PRIMARY KEY',
                'sqlite' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
                default  => throw new \RuntimeException("Driver não suportado: {$driver}"),
            },
            'string' => match ($driver) {
                'mysql', 'pgsql' => 'VARCHAR(' . ($col['length'] ?? 255) . ')',
                'sqlite'         => 'TEXT',
                default          => throw new \RuntimeException("Driver não suportado: {$driver}"),
            },
            'text' => 'TEXT',
            'integer' => match ($driver) {
                'mysql'  => 'INT',
                'pgsql'  => 'INTEGER',
                'sqlite' => 'INTEGER',
                default  => throw new \RuntimeException("Driver não suportado: {$driver}"),
            },
            'decimal' => match ($driver) {
                'mysql'  => 'DECIMAL(' . ($col['precision'] ?? 10) . ',' . ($col['scale'] ?? 2) . ')',
                'pgsql'  => 'NUMERIC(' . ($col['precision'] ?? 10) . ',' . ($col['scale'] ?? 2) . ')',
                'sqlite' => 'NUMERIC',
                default  => throw new \RuntimeException("Driver não suportado: {$driver}"),
            },
            'boolean' => match ($driver) {
                'mysql'  => 'TINYINT(1)',
                'pgsql'  => 'BOOLEAN',
                'sqlite' => 'INTEGER',
                default  => throw new \RuntimeException("Driver não suportado: {$driver}"),
            },
            'datetime' => match ($driver) {
                'mysql'  => 'DATETIME',
                'pgsql'  => 'TIMESTAMP',
                'sqlite' => 'TEXT',
                default  => throw new \RuntimeException("Driver não suportado: {$driver}"),
            },
            'date' => match ($driver) {
                'mysql'  => 'DATE',
                'pgsql'  => 'DATE',
                'sqlite' => 'TEXT',
                default  => throw new \RuntimeException("Driver não suportado: {$driver}"),
            },
            'time' => match ($driver) {
                'mysql'  => 'TIME',
                'pgsql'  => 'TIME',
                'sqlite' => 'TEXT',
                default  => throw new \RuntimeException("Driver não suportado: {$driver}"),
            },
            'json' => match ($driver) {
                'mysql'  => 'JSON',
                'pgsql'  => 'JSONB',
                'sqlite' => 'TEXT',
                default  => throw new \RuntimeException("Driver não suportado: {$driver}"),
            },
            'uuid' => match ($driver) {
                'mysql'  => 'CHAR(36)',
                'pgsql'  => 'UUID',
                'sqlite' => 'TEXT',
                default  => throw new \RuntimeException("Driver não suportado: {$driver}"),
            },
            'foreignId' => match ($driver) {
                'mysql'  => 'INT',
                'pgsql'  => 'INTEGER',
                'sqlite' => 'INTEGER',
                default  => throw new \RuntimeException("Driver não suportado: {$driver}"),
            },
            default => throw new \RuntimeException("Tipo não suportado: {$col['type']}"),
        };
    }

    private function compileDefault(mixed $value, string $type, string $driver): string
    {
        if (is_bool($value)) {
            return match ($driver) {
                'pgsql'  => $value ? 'TRUE' : 'FALSE',
                'mysql', 'sqlite' => $value ? '1' : '0',
                default  => throw new \RuntimeException("Driver não suportado: {$driver}"),
            };
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return "'" . str_replace("'", "''", (string) $value) . "'";
    }

    private function quote(string $identifier): string
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
            throw new \InvalidArgumentException("Invalid identifier: {$identifier}");
        }
        return '"' . $identifier . '"';
    }
}
