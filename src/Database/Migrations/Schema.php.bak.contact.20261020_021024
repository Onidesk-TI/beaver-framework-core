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

use Beaver\Database\Database;

/**
 * Fachada estática para usar nas migrations.
 *
 * Aceita dois formatos:
 *
 *   1. Closure (clássico):
 *      Schema::create('users', function (Blueprint $t) {
 *          $t->id();
 *          $t->string('name');
 *          $t->timestamps();
 *      });
 *
 *   2. Array (compacto):
 *      Schema::create('users', [
 *          'id',
 *          'name:str',
 *          'email:str?',
 *          'amount:dec(10,2)',
 *          'user:ref.users',
 *          'timestamps',
 *      ]);
 *
 * Outros métodos:
 *   Schema::drop('users');
 *   Schema::has('users');
 */
class Schema
{
    public static function create(string $table, callable|array $definition): void
    {
        $blueprint = new Blueprint($table);

        if (is_callable($definition)) {
            $definition($blueprint);
        } else {
            self::applyArrayDefinition($blueprint, $definition);
        }

        $db     = Database::getInstance();
        $driver = $db->getDriverName();

        $db->exec($blueprint->toCreateSql($driver));

        foreach ($blueprint->toIndexSql($driver) as $sql) {
            $db->exec($sql);
        }
    }

    public static function drop(string $table): void
    {
        Database::getInstance()->exec(sprintf('DROP TABLE IF EXISTS "%s"', $table));
    }

    public static function dropIfExists(string $table): void
    {
        self::drop($table);
    }

    public static function has(string $table): bool
    {
        return Database::getInstance()->tableExists($table);
    }

    // ---------- Array definition ----------

    /**
     * Aplica um array de campos ao Blueprint.
     *
     * Aceita:
     *   - strings: 'name:str', 'amount:dec(10,2)'
     *   - arrays:  ['name' => 'name', 'type' => 'str', 'length' => 100, 'nullable' => true]
     */
    private static function applyArrayDefinition(Blueprint $blueprint, array $definition): void
    {
        foreach ($definition as $key => $value) {
            // Formato: [ 'name:str', 'amount:dec', ... ]   (chave é numérica, valor é string)
            if (is_int($key) && is_string($value)) {
                (new FieldParser($value))->applyTo($blueprint);
                continue;
            }

            // Formato: [ 'id', 'name:str', ... ]   (mesma coisa, com chave explícita)
            if (is_int($key) && is_array($value)) {
                // ['name' => 'name', 'type' => 'str', 'nullable' => true]
                self::applyArrayField($blueprint, $value);
                continue;
            }

            // Formato associativo: ['name' => 'string', 'amount' => 'decimal']
            if (is_string($key) && is_string($value)) {
                (new FieldParser("{$key}:{$value}"))->applyTo($blueprint);
                continue;
            }
        }
    }

    /**
     * Aplica um campo definido como array associativo.
     *
     * Uso:
     *   ['name' => 'email', 'type' => 'str', 'length' => 255, 'nullable' => true, 'default' => 'x']
     */
    private static function applyArrayField(Blueprint $blueprint, array $field): void
    {
        if (!isset($field['name'])) {
            return;
        }

        // Construir a sintaxe compacta e deixar o parser fazer o trabalho
        $syntax = $field['name'];
        $syntax .= ':' . ($field['type'] ?? 'str');

        if (isset($field['length'])) {
            $syntax .= '(' . $field['length'] . ')';
        } elseif (isset($field['precision'])) {
            $syntax .= '(' . $field['precision'];
            if (isset($field['scale'])) {
                $syntax .= ',' . $field['scale'];
            }
            $syntax .= ')';
        }

        if (!empty($field['nullable'])) {
            $syntax .= '?';
        }

        if (array_key_exists('default', $field) && $field['default'] !== null) {
            $default = $field['default'];
            if (is_bool($default)) {
                $default = $default ? '1' : '0';
            }
            $syntax .= '=' . $default;
        }

        (new FieldParser($syntax))->applyTo($blueprint);
    }


        /**
     * Executa SQL cru.
     *
     * Útil para triggers, procedures, views, etc.
     * — coisas que não são cross-driver.
     *
     * Exemplo:
     *   Schema::raw("CREATE TRIGGER ... FOR EACH ROW SET NEW.updated_at = NOW()");
     *
     * @param string $sql       SQL a executar
     * @param array  $bindings  Parâmetros (opcional)
     */
    public static function raw(string $sql, array $bindings = []): void
    {
        $db = Database::getInstance();

        if ($bindings === []) {
            $db->exec($sql);
            return;
        }

        $db->update($sql, $bindings);
    }

    /**
     * Devolve o driver ativo (sqlite, mysql, pgsql).
     *
     * Útil em migrations que precisam de SQL específico por driver:
     *
     *   match (Schema::driver()) {
     *       'mysql'  => ...,
     *       'pgsql'  => ...,
     *       'sqlite' => ...,
     *   };
     */
    public static function driver(): string
    {
        return Database::getInstance()->getDriverName();
    }
}
