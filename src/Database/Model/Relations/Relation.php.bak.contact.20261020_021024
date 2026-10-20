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

namespace Beaver\Database\Model\Relations;

use Beaver\Database\Model\Collection;
use Beaver\Database\Model\Model;
use Beaver\Database\Model\QueryBuilder;

/**
 * Classe base para todas as relações.
 *
 * Uma relação guarda:
 *   - o Model "pai" (quem chamou belongsTo/hasMany)
 *   - a classe do Model "relacionado"
 *   - as chaves (local / foreign / owner)
 *
 * E sabe construir um QueryBuilder já com o WHERE correto.
 *
 * As subclasses só precisam de implementar:
 *   - getResults(): mixed   (corre a query e devolve Model|Collection|null)
 *   - get(): mixed          (aplica cache + chama getResults)
 */
abstract class Relation
{
    /** Model pai (dono da relação). */
    protected Model $parent;

    /** @var class-string<Model> */
    protected string $related;

    /** Chave estrangeira na tabela relacionada (ou pivot). */
    protected string $foreignKey;

    /** Chave local (no parent). */
    protected string $localKey;

    public function __construct(
        Model $parent,
        string $related,
        string $foreignKey,
        string $localKey,
    ) {
        if (!is_subclass_of($related, Model::class)) {
            throw new \InvalidArgumentException(
                "Relation expects a Model subclass, got: {$related}"
            );
        }

        $this->parent     = $parent;
        $this->related    = $related;
        $this->foreignKey = $foreignKey;
        $this->localKey   = $localKey;
    }

    /**
     * Resolve a relação (devolve Model|Collection|null).
     *
     * O Model::__get() chama isto. Subclasses devem aplicar
     * cache local (evitar 2 queries para o mesmo atributo).
     */
    abstract public function get(): mixed;

    /** QueryBuilder já com o WHERE base da relação. */
    abstract protected function query(): QueryBuilder;

    // ---------- Helpers ----------

    /** Valor da chave local no parent (ex: $produto->id). */
    protected function localValue(): mixed
    {
        return $this->parent->{$this->localKey};
    }

    /** Nome da tabela relacionada. */
    protected function relatedTable(): string
    {
        return ($this->related)::table();
    }

    /** Nome da tabela do parent. */
    protected function parentTable(): string
    {
        return $this->parent::table();
    }

    /** Nome da chave primária do parent. */
    protected function parentKeyName(): string
    {
        return $this->parent::primaryKey();
    }

    /** Nome da chave primária do relacionado. */
    protected function relatedKeyName(): string
    {
        return ($this->related)::primaryKey();
    }
}
