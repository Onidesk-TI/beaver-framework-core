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
 * Relação 1:1 — o Model atual tem exatamente um relacionado.
 *
 *     class User extends Model {
 *         public function perfil(): HasOne {
 *             return $this->hasOne(Perfil::class, 'user_id');
 *         }
 *     }
 *
 *     $user->perfil;          // Perfil|null
 *     $user->perfil()->get(); // idem
 *
 * Se houver mais que um, `get()` devolve o primeiro.
 */
class HasOne extends Relation
{
    private ?Model $cached = null;
    private bool $resolved = false;

    public function get(): ?Model
    {
        if ($this->resolved) {
            return $this->cached;
        }

        $this->resolved = true;
        $this->cached   = $this->query()->first();

        return $this->cached;
    }

    protected function query(): QueryBuilder
    {
        return ($this->related)::query()
            ->where($this->foreignKey, '=', $this->localValue());
    }

    /**
     * Cria um registo relacionado já com a FK preenchida.
     *
     *     $user->perfil()->create(['bio' => '...']);
     */
    public function create(array $attributes): Model
    {
        $attributes[$this->foreignKey] = $this->localValue();

        $model = ($this->related)::create($attributes);

        $this->cached   = $model;
        $this->resolved = true;

        return $model;
    }
}
