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
 * Wrapper mínimo que faz um SELECT cru e hidrata Models.
 *
 * Usado internamente pelas relações que precisam de JOIN
 * (BelongsToMany) — o QueryBuilder atual não suporta JOIN.
 *
 * Não uses isto diretamente em código de aplicação.
 */
class RawQueryBuilder
{
    /** @var class-string<Model> */
    private string $modelClass;
    private string $sql;
    /** @var array<string, mixed> */
    private array $bindings;

    /**
     * @param class-string<Model> $modelClass
     * @param array<string, mixed> $bindings
     */
    public function __construct(string $modelClass, string $sql, array $bindings = [])
    {
        if (!is_subclass_of($modelClass, Model::class)) {
            throw new \InvalidArgumentException(
                "RawQueryBuilder expects a Model subclass, got: {$modelClass}"
            );
        }

        $this->modelClass = $modelClass;
        $this->sql        = $sql;
        $this->bindings   = $bindings;
    }

    /** @return Model[] */
    public function get(): array
    {
        $rows = Database::getInstance()->select($this->sql, $this->bindings);

        return array_map(
            fn (array $row) => $this->modelClass::hydrate($row),
            $rows,
        );
    }

    public function first(): ?Model
    {
        $rows = $this->get();
        return $rows[0] ?? null;
    }

    public function count(): int
    {
        return count($this->get());
    }
}
