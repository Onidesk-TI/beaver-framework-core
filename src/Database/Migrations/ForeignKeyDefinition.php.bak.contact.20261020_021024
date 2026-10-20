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

/**
 * Configura uma foreign key depois de a criar.
 *
 * Uso:
 *   $t->foreignId('user_id')->references('users');
 *   $t->foreignId('user_id')->references('users', 'uuid');
 */
class ForeignKeyDefinition
{
    private Blueprint $blueprint;
    private string $column;

    public function __construct(Blueprint $blueprint, string $column)
    {
        $this->blueprint = $blueprint;
        $this->column = $column;
    }

    public function references(string $table, string $foreignColumn = 'id'): self
    {
        // Registar a FK no Blueprint
        // (implementação a fazer no Blueprint::toCreateSql)
        $this->blueprint->addForeignKey($this->column, $table, $foreignColumn);
        return $this;
    }
}
