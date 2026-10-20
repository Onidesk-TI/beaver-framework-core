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
 * Base class para todas as migrations.
 *
 * Cada migration é um ficheiro em `database/migrations/` que devolve
 * uma instância anónima de Migration:
 *
 *     return new class extends Migration {
 *         public function up(): void
 *         {
 *             Schema::create('users', function (Blueprint $t) {
 *                 $t->id();
 *                 $t->string('name');
 *                 $t->timestamps();
 *             });
 *         }
 *
 *         public function down(): void
 *         {
 *             Schema::drop('users');
 *         }
 *     };
 *
 * O `up()` cria/altera tabelas. O `down()` reverte.
 */
abstract class Migration
{
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** Aplica a migration (cria/altera tabelas). */
    abstract public function up(): void;

    /** Reverte a migration (apaga/restaura tabelas). */
    abstract public function down(): void;
}
