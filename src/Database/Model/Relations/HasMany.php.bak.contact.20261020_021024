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
 * Relação 1:n — o Model atual tem muitos relacionados.
 *
 *     class Produto extends Model {
 *         public function imagens(): HasMany {
 *             return $this->hasMany(Imagem::class, 'produto_id');
 *         }
 *     }
 *
 *     foreach ($produto->imagens as $img) { ... }
 *     $produto->imagens()->count();
 *     $produto->imagens()->where('principal', true)->get();
 *     $produto->imagens()->create(['url' => '...']);
 */
class HasMany extends Relation
{
    private ?Collection $cached = null;
    private bool $resolved = false;

    /** @return Collection<int, Model> */
    public function get(): Collection
    {
        if ($this->resolved) {
            return $this->cached;
        }

        $this->resolved = true;
        $this->cached   = new Collection($this->query()->get());

        return $this->cached;
    }

    protected function query(): QueryBuilder
    {
        return ($this->related)::query()
            ->where($this->foreignKey, '=', $this->localValue());
    }

    // ---------- Terminal (delega no QueryBuilder) ----------

    public function count(): int
    {
        return $this->query()->count();
    }

    public function exists(): bool
    {
        return $this->query()->exists();
    }

    /**
     * Cria um registo relacionado já com a FK preenchida.
     *
     *     $produto->imagens()->create(['url' => 'a.jpg']);
     */
    public function create(array $attributes): Model
    {
        $attributes[$this->foreignKey] = $this->localValue();

        return ($this->related)::create($attributes);
    }

    /**
     * Acesso fluente ao QueryBuilder (para where/orderBy encadeados).
     *
     *     $produto->imagens()->where('principal', true)->get();
     */
    public function where(string $column, mixed $operator, mixed $value = null): QueryBuilder
    {
        return $this->query()->where($column, $operator, $value);
    }

    public function orderBy(string $column, string $direction = 'asc'): QueryBuilder
    {
        return $this->query()->orderBy($column, $direction);
    }

    public function limit(int $limit): QueryBuilder
    {
        return $this->query()->limit($limit);
    }
}
