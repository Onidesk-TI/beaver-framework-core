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

use Beaver\Database\Model\Model;
use Beaver\Database\Model\QueryBuilder;

/**
 * Relação n:1 — o Model atual pertence a outro.
 *
 *     class Produto extends Model {
 *         public function categoria(): BelongsTo {
 *             return $this->belongsTo(Categoria::class, 'categoria_id');
 *         }
 *     }
 *
 *     $produto->categoria;          // Categoria|null
 *     $produto->categoria()->get(); // idem
 *     $produto->categoria()->associate($cat);  // seta categoria_id
 *     $produto->categoria()->dissociate();     // categoria_id = null
 */
class BelongsTo extends Relation
{
    /** Cache do resultado (evita 2 queries). */
    private ?Model $cached = null;
    private bool $resolved = false;

    public function __construct(Model $parent, string $related, string $foreignKey, string $ownerKey)
    {
        parent::__construct($parent, $related, $foreignKey, $ownerKey);
    }

    public function get(): ?Model
    {
        if ($this->resolved) {
            return $this->cached;
        }

        $this->resolved = true;
        $this->cached   = $this->query()->first();

        return $this->cached;
    }

    /**
     * QueryBuilder já com WHERE owner_key = <valor da fk no parent>.
     */
    protected function query(): QueryBuilder
    {
        return ($this->related)::query()
            ->where($this->localKey, '=', $this->foreignValue());
    }

    /** Valor da FK no parent (ex: $produto->categoria_id). */
    protected function foreignValue(): mixed
    {
        return $this->parent->{$this->foreignKey};
    }

    // ---------- Mutações ----------

    /**
     * Associa um Model pai a este filho.
     *     $produto->categoria()->associate($cat);
     *     $produto->save();
     */
    public function associate(Model $parent): Model
    {
        $key = $parent::primaryKey();
        $this->parent->{$this->foreignKey} = $parent->{$key};
        $this->cached   = $parent;
        $this->resolved = true;

        return $this->parent;
    }

    /**
     * Desassocia (FK = null).
     *     $produto->categoria()->dissociate();
     *     $produto->save();
     */
    public function dissociate(): Model
    {
        $this->parent->{$this->foreignKey} = null;
        $this->cached   = null;
        $this->resolved = true;

        return $this->parent;
    }
}
