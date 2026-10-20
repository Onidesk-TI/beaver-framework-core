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

use Beaver\Database\Database;
use Beaver\Database\Model\Collection;
use Beaver\Database\Model\Model;
use Beaver\Database\Model\QueryBuilder;

/**
 * Relação n:n via tabela pivot.
 *
 *     class Post extends Model {
 *         public function tags(): BelongsToMany {
 *             return $this->belongsToMany(
 *                 Tag::class,           // related
 *                 'post_tag',           // pivot table
 *                 'post_id',            // FK do parent no pivot
 *                 'tag_id',             // FK do related no pivot
 *             );
 *         }
 *     }
 *
 *     $post->tags;                 // Collection<Tag>
 *     $post->tags()->attach($tagId);
 *     $post->tags()->detach($tagId);
 *     $post->tags()->sync([1, 2, 3]);
 */
class BelongsToMany extends Relation
{
    protected string $pivotTable;
    protected string $pivotForeignKey;   // FK do parent no pivot
    protected string $pivotRelatedKey;   // FK do related no pivot

    private ?Collection $cached = null;
    private bool $resolved = false;

    public function __construct(
        Model $parent,
        string $related,
        string $pivotTable,
        string $pivotForeignKey,
        string $pivotRelatedKey,
    ) {
        // Na base, foreignKey = chave local no parent (PK).
        // localKey     = PK do related (para o SELECT).
        parent::__construct(
            $parent,
            $related,
            $pivotForeignKey,
            $pivotRelatedKey,
        );

        $this->pivotTable      = $pivotTable;
        $this->pivotForeignKey = $pivotForeignKey;
        $this->pivotRelatedKey = $pivotRelatedKey;
    }

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

    /**
     * SELECT related.* FROM related
     *   INNER JOIN pivot ON pivot.pivot_related_key = related.pk
     *   WHERE pivot.pivot_foreign_key = <pk do parent>
     */
    protected function query(): QueryBuilder
    {
        // O QueryBuilder atual não suporta JOIN. Fazemos SQL manual.
        $relatedTable = $this->relatedTable();
        $relatedPk    = $this->relatedKeyName();
        $parentPk     = $this->parentKeyName();

        $sql = sprintf(
            'SELECT r.* FROM %s r ' .
            'INNER JOIN %s p ON p.%s = r.%s ' .
            'WHERE p.%s = :parent_id',
            $this->quoteIdentifier($relatedTable),
            $this->quoteIdentifier($this->pivotTable),
            $this->quoteIdentifier($this->pivotRelatedKey),
            $this->quoteIdentifier($relatedPk),
            $this->quoteIdentifier($this->pivotForeignKey),
        );

        // Como o QueryBuilder não aceita SQL cru, fazemos um wrapper
        // mínimo que devolve os rows já hidratados.
        return new RawQueryBuilder($this->related, $sql, [
            ':parent_id' => $this->parent->{$parentPk},
        ]);
    }

    // ---------- Mutações no pivot ----------

    /**
     * Insere uma linha no pivot.
     *     $post->tags()->attach($tagId);
     *     $post->tags()->attach($tagId, ['ordem' => 1]);
     */
    public function attach(int|string $relatedId, array $extra = []): void
    {
        $parentId = $this->parent->{$this->parentKeyName()};

        $columns = [$this->pivotForeignKey, $this->pivotRelatedKey];
        $values  = [$parentId, $relatedId];

        foreach ($extra as $col => $val) {
            $columns[] = $col;
            $values[]  = $val;
        }

        $placeholders = array_map(fn ($c) => ':' . $c, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->quoteIdentifier($this->pivotTable),
            implode(', ', array_map([$this, 'quoteIdentifier'], $columns)),
            implode(', ', $placeholders),
        );

        $params = [];
        foreach ($columns as $i => $col) {
            $params[':' . $col] = $values[$i];
        }

        Database::getInstance()->insert($sql, $params);
    }

    /**
     * Remove uma linha do pivot.
     *     $post->tags()->detach($tagId);
     *     $post->tags()->detach();      // remove todas
     */
    public function detach(int|string|null $relatedId = null): void
    {
        $parentId = $this->parent->{$this->parentKeyName()};

        $sql = sprintf(
            'DELETE FROM %s WHERE %s = :parent_id',
            $this->quoteIdentifier($this->pivotTable),
            $this->quoteIdentifier($this->pivotForeignKey),
        );

        $params = [':parent_id' => $parentId];

        if ($relatedId !== null) {
            $sql .= ' AND ' . $this->quoteIdentifier($this->pivotRelatedKey) . ' = :related_id';
            $params[':related_id'] = $relatedId;
        }

        Database::getInstance()->delete($sql, $params);
    }

    /**
     * Substitui as linhas do pivot pelas IDs dadas.
     *     $post->tags()->sync([1, 2, 3]);
     */
    public function sync(array $relatedIds): void
    {
        $this->detach();

        foreach ($relatedIds as $id) {
            $this->attach($id);
        }
    }

    // ---------- Internos ----------

    protected function quoteIdentifier(string $name): string
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            throw new \InvalidArgumentException("Invalid identifier: {$name}");
        }
        return '"' . $name . '"';
    }
}
