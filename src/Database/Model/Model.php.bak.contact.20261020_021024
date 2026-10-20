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
 * Base Model — Active Record.
 *
 * Mapeia uma classe para uma tabela. O nome da tabela vem da
 * propriedade estática $table, ou é inferido do nome da classe
 * (snake_case + plural simples).
 *
 * Exemplo de uso:
 *
 *     use Beaver\Database\Model\Model;
 *
 *     class ScheduledJob extends Model
 *     {
 *         protected static string $table = 'scheduled_jobs';
 *         protected array $fillable = ['name', 'handler', 'cron_expression'];
 *     }
 *
 *     $job = ScheduledJob::find(1);
 *     $job->is_active = false;
 *     $job->save();
 *
 *     ScheduledJob::create(['name' => 'X', 'cron_expression' => '0 9 * * *']);
 *
 * Suporta SQLite, MySQL e PostgreSQL (via Beaver\Database\Database).
 */
abstract class Model
{
    /** Nome da tabela. Se vazio, é inferido do nome da classe. */
    protected static string $table = '';

    /** Chave primária. */
    protected static string $primaryKey = 'id';

    /** Campos preenchíveis em massa (create / fill). */
    protected array $fillable = [];

    /** Campos escondidos em toArray() / toJson(). */
    protected array $hidden = [];

    /** Timestamps automáticos (created_at / updated_at). */
    protected static bool $timestamps = true;

    /** Atributos atuais (colunas da BD + valores em memória). */
    protected array $attributes = [];

    /** Atributos alterados desde o último save. */
    protected array $dirty = [];

    /** true se o registo já existe na BD. */
    protected bool $exists = false;


   /** @var array<string, string> casts por campo, ex: ['published_at' => 'datetime'] */
    protected array $casts = [];

/**
 * Driver de datas por Model.
 *
 * Vazio → usa o global (env BEAVER_DATE_DRIVER → config → 'beaver').
 * Preenchido → força este driver para este Model.
 *
 * Valores possíveis:
 *   'beaver' → \Beaver\Database\Model\Casts\BeaverDate   (default, nativo)
 *   'carbon' → \Beaver\Database\Model\Casts\CarbonDate   (requer nesbot/carbon)
 *
 * Como trocar para Carbon:
 *   1. composer require nesbot/carbon
 *   2. No .env do framework: BEAVER_DATE_DRIVER=carbon
 *      (ou define aqui: protected static string $dateDriver = 'carbon';)
 */
    protected static string $dateDriver = '';

/** @var array<class-string, array<string, string>> cache por classe */
    private static array $castClassesCache = [];


/** Casts complexos (classes) — mapa tipo → classe */
    protected static array $castClasses = [
    'date'     => Casts\BeaverDate::class,
    'datetime' => Casts\BeaverDate::class,
    ];

    // ---------- Configuração ----------

    public static function table(): string
    {
        if (static::$table !== '') {
            return static::$table;
        }

        // 'ScheduledJob' → 'scheduled_jobs'
        $class = (new \ReflectionClass(static::class))->getShortName();
        $snake = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class));

        return $snake . 's';
    }

    public static function primaryKey(): string
    {
        return static::$primaryKey;
    }

    public static function usesTimestamps(): bool
    {
        return static::$timestamps;
    }

    // ---------- Leitura ----------

    public static function find(int|string $id): ?static
    {
        return static::query()
            ->where(static::primaryKey(), '=', $id)
            ->first();
    }

    public static function findOrFail(int|string $id): static
    {
        $model = static::find($id);

        if ($model === null) {
            throw new \RuntimeException(sprintf(
                '%s with %s = %s not found',
                static::class,
                static::primaryKey(),
                $id,
            ));
        }

        return $model;
    }

    /** @return static[] */
    public static function all(): array
    {
        return static::query()->get();
    }

    public static function query(): QueryBuilder
    {
        return new QueryBuilder(static::class);
    }

    public static function where(string $column, mixed $operatorOrValue, mixed $value = null): QueryBuilder
    {
        $qb = static::query();

    // where('x', 1) → where('x', '=', 1)
        if (func_num_args() === 2) {
            return $qb->where($column, '=', $operatorOrValue);
        }

    // where('x', '>', 1)
        return $qb->where($column, $operatorOrValue, $value);
    }

    public static function orderBy(string $column, string $direction = 'asc'): QueryBuilder
    {
        return static::query()->orderBy($column, $direction);
    }

    // ---------- Escrita ----------

    public static function create(array $attributes): static
    {
        $model = new static();
        $model->fill($attributes);
        $model->save();
        return $model;
    }

    /** Constrói uma instância a partir de uma linha da BD (sem marcar dirty). */
    public static function hydrate(array $row): static
    {
        $model = new static();
        $model->attributes = $row;
        $model->exists = true;
        return $model;
    }

    // ---------- Acesso a atributos ----------
    public function __get(string $name): mixed
    {
        //  Atributo real na BD tem prioridade
        if (array_key_exists($name, $this->attributes)) {
            return $this->castValue($name, $this->attributes[$name]);
        }

        //  Se não existe como atributo, tenta resolver como relação
        return $this->resolveRelation($name);
    }


    public function __set(string $name, mixed $value): void
    {
        // Se for cast json/array e o valor for array/object, converter para string
        if (
            isset($this->casts[$name])
            && in_array($this->casts[$name], ['array', 'json', 'object'], true)
        ) {
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }
        }

        if (array_key_exists($name, $this->attributes) && $this->attributes[$name] === $value) {
            return;
        }

        $this->attributes[$name] = $value;
        $this->dirty[$name] = true;
    }

    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    public function fill(array $data): static
    {
        foreach ($data as $key => $value) {
            if ($this->fillable !== [] && !in_array($key, $this->fillable, true)) {
                continue;
            }
            $this->{$key} = $value;
        }
        return $this;
    }

    public function forceFill(array $data): static
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
        return $this;
    }

    public function toArray(): array
    {
        $out = $this->attributes;
        foreach ($this->hidden as $key) {
            unset($out[$key]);
        }
        return $out;
    }

    public function toJson(int $flags = 0): string
    {
        return json_encode($this->toArray(), $flags) ?: '{}';
    }

    // ---------- Persistência ----------

    public function save(): bool
    {
        $db    = Database::getInstance();
        $table = static::table();

        if (static::usesTimestamps()) {
            $now = date('Y-m-d H:i:s');

            if (!$this->exists) {
                $this->attributes['created_at'] ??= $now;
            }
            $this->attributes['updated_at'] = $now;
            $this->dirty['updated_at'] = true;
        }

        if (!$this->exists) {
            return $this->performInsert($db, $table);
        }

        return $this->performUpdate($db, $table);
    }

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $db = Database::getInstance();
        $pk = static::primaryKey();

        if (!isset($this->attributes[$pk])) {
            throw new \RuntimeException('Cannot delete a model without a primary key value');
        }

        $sql = sprintf(
            'DELETE FROM %s WHERE %s = :id',
            $this->quoteIdentifier(static::table()),
            $this->quoteIdentifier($pk),
        );

        $db->delete($sql, [':id' => $this->attributes[$pk]]);
        $this->exists = false;

        return true;
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    public function getDirty(): array
    {
        return $this->dirty;
    }

    // ---------- Internos ----------

    private function performInsert(Database $db, string $table): bool
    {
        $columns      = array_keys($this->attributes);
        $quotedCols   = array_map([$this, 'quoteIdentifier'], $columns);
        $placeholders = array_map(fn ($c) => ':' . $c, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->quoteIdentifier($table),
            implode(', ', $quotedCols),
            implode(', ', $placeholders),
        );

        $params = [];
        foreach ($this->attributes as $k => $v) {
            $params[':' . $k] = $v;
        }

        $db->insert($sql, $params);

        $pk = static::primaryKey();
        if (!isset($this->attributes[$pk])) {
            $id = $db->getPdo()->lastInsertId();
            if ($id !== false && $id !== '0' && $id !== '') {
                $this->attributes[$pk] = $id;
            }
        }

        $this->exists = true;
        $this->dirty  = [];

        return true;
    }

    private function performUpdate(Database $db, string $table): bool
    {
        if ($this->dirty === []) {
            return true;
        }

        $pk    = static::primaryKey();
        $sets  = [];
        $params = [];

        foreach (array_keys($this->dirty) as $col) {
            if ($col === $pk) {
                continue;
            }
            $sets[] = $this->quoteIdentifier($col) . ' = :' . $col;
            $params[':' . $col] = $this->attributes[$col] ?? null;
        }

        if ($sets === []) {
            $this->dirty = [];
            return true;
        }

        if (!isset($this->attributes[$pk])) {
            throw new \RuntimeException('Cannot update a model without a primary key value');
        }

        $params[':__pk'] = $this->attributes[$pk];

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s = :__pk',
            $this->quoteIdentifier($table),
            implode(', ', $sets),
            $this->quoteIdentifier($pk),
        );

        $db->update($sql, $params);

        $this->dirty = [];

        return true;
    }

    /**
     * Aspas de identificadores.
     * Usa `"` (ANSI) — funciona em SQLite e PostgreSQL.
     * Para MySQL, ativa `sql_mode=ANSI_QUOTES`, ou adapta aqui.
     */
    protected function quoteIdentifier(string $name): string
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            throw new \InvalidArgumentException("Invalid identifier: {$name}");
        }
        return '"' . $name . '"';
    }

    /**
 * Aplica o cast definido em $casts a um valor lido da BD.
 *
 * Chamado por __get() sempre que acede a um atributo. Se o
 * campo não estiver em $casts, devolve o valor tal como veio
 * da BD (tipicamente uma string em SQLite).
 *
 * Casts suportados:
 *
 *   'int'      → (int)
 *   'integer'  → (int)
 *   'float'    → (float)
 *   'double'   → (float)
 *   'bool'     → (bool)
 *   'boolean'  → (bool)
 *   'string'   → (string)
 *   'array'    → json_decode como array associativo
 *   'json'     → igual a 'array'
 *   'object'   → json_decode como stdClass
 *   'date'     → \DateTimeImmutable
 *   'datetime' → \DateTimeImmutable
 *   'timestamp'=> (int) ou strtotime()
 *
 * Se a coluna for NULL, devolve NULL sem tocar.
 *
 * Exemplo:
 *   protected array $casts = [
 *       'published_at' => 'datetime',
 *       'is_active'    => 'bool',
 *       'metadata'     => 'array',
 *       'amount'       => 'float',
 *   ];
 *
 * @param string $key    Nome do atributo (coluna)
 * @param mixed  $value  Valor cru vindo da BD
 * @return mixed         Valor com cast aplicado (ou null)
 */
    private function castValue(string $key, mixed $value): mixed
    {
        if ($value === null || !isset($this->casts[$key])) {
            return $value;
        }

        $type = $this->casts[$key];

    // Cast simples (int, bool, float, string) → match direto
    // Cast complexo (date, datetime, array, json) → classe dedicada
        $classes = static::castClasses();

        if (isset($classes[$type])) {
            return $classes[$type]::apply($value);
        }

        return match ($type) {
            'int', 'integer'  => (int) $value,
            'float', 'double' => (float) $value,
            'bool', 'boolean' => (bool) $value,
            'string'          => (string) $value,
            'array', 'json'   => is_string($value) ? (json_decode($value, true) ?? []) : $value,
            'object'          => is_string($value) ? (json_decode($value) ?? new \stdClass()) : $value,
            default           => $value,
        };
    }

    /**
 * Resolve as classes de cast para datas.
 *
 * Ordem de prioridade:
 *   1. $dateDriver do Model (se não vazio)
 *   2. BEAVER_DATE_DRIVER do .env
 *   3. config('app.dates.driver')
 *   4. 'beaver' (default)
 *
 * O cache é por classe concreta (static::class), para que
 * Models diferentes possam ter drivers diferentes.
 *
 * @return array<string, string>
 */
    protected static function castClasses(): array
    {
        $className = static::class;

        if (isset(self::$castClassesCache[$className])) {
            return self::$castClassesCache[$className];
        }

        // 1. Por Model
        $driver = static::$dateDriver;

        // 2. Do .env
        if ($driver === '') {
            $env = getenv('BEAVER_DATE_DRIVER');
            $driver = $env !== false ? $env : '';
        }

        // 3. Da config
        if ($driver === '') {
            try {
                $driver = \Beaver\Foundation\Application::getInstance()
                ->config('app.dates.driver', 'beaver');
            } catch (\Throwable) {
                $driver = 'beaver';
            }
        }

        // 4. Default
        if ($driver === '') {
            $driver = 'beaver';
        }

        // Se for 'carbon' mas o Carbon não estiver instalado,
        // usa 'beaver' como fallback silencioso.
        if ($driver === 'carbon' && !class_exists(\Carbon\CarbonImmutable::class)) {
            $driver = 'beaver';
        }

        $dateClass = match ($driver) {
            'carbon' => Casts\CarbonDate::class,
            default  => Casts\BeaverDate::class,
        };

        return self::$castClassesCache[$className] = [
        'date'     => $dateClass,
        'datetime' => $dateClass,
        ];
    }



        // ---------- Relações ----------

    /**
     * Relação n:1 — este Model pertence a outro.
     *
     *     public function categoria(): BelongsTo {
     *         return $this->belongsTo(Categoria::class, 'categoria_id');
     *     }
     *
     * @param class-string<Model> $related
     * @param string|null $foreignKey  FK na tabela deste Model (default: <related>_id)
     * @param string|null $ownerKey    PK do related (default: PK do related)
     */
    public function belongsTo(
        string $related,
        ?string $foreignKey = null,
        ?string $ownerKey = null,
    ): Relations\BelongsTo {
        $foreignKey ??= $this->defaultForeignKey($related);
        $ownerKey   ??= $related::primaryKey();

        return new Relations\BelongsTo($this, $related, $foreignKey, $ownerKey);
    }

    /**
     * Relação 1:1 — este Model tem exatamente um relacionado.
     *
     *     public function perfil(): HasOne {
     *         return $this->hasOne(Perfil::class, 'user_id');
     *     }
     *
     * @param class-string<Model> $related
     * @param string|null $foreignKey  FK na tabela related (default: <este>_id)
     * @param string|null $localKey    PK deste Model (default: PK)
     */
    public function hasOne(
        string $related,
        ?string $foreignKey = null,
        ?string $localKey = null,
    ): Relations\HasOne {
        $foreignKey ??= $this->defaultForeignKey(static::class);
        $localKey   ??= static::primaryKey();

        return new Relations\HasOne($this, $related, $foreignKey, $localKey);
    }

    /**
     * Relação 1:n — este Model tem muitos relacionados.
     *
     *     public function imagens(): HasMany {
     *         return $this->hasMany(Imagem::class, 'produto_id');
     *     }
     *
     * @param class-string<Model> $related
     * @param string|null $foreignKey  FK na tabela related (default: <este>_id)
     * @param string|null $localKey    PK deste Model (default: PK)
     */
    public function hasMany(
        string $related,
        ?string $foreignKey = null,
        ?string $localKey = null,
    ): Relations\HasMany {
        $foreignKey ??= $this->defaultForeignKey(static::class);
        $localKey   ??= static::primaryKey();

        return new Relations\HasMany($this, $related, $foreignKey, $localKey);
    }

    /**
     * Relação n:n via tabela pivot.
     *
     *     public function tags(): BelongsToMany {
     *         return $this->belongsToMany(Tag::class, 'post_tag', 'post_id', 'tag_id');
     *     }
     *
     * @param class-string<Model> $related
     * @param string|null $pivotTable       Nome da tabela pivot
     * @param string|null $pivotForeignKey  FK do parent no pivot
     * @param string|null $pivotRelatedKey  FK do related no pivot
     */
    public function belongsToMany(
        string $related,
        ?string $pivotTable = null,
        ?string $pivotForeignKey = null,
        ?string $pivotRelatedKey = null,
    ): Relations\BelongsToMany {
        $parentShort = $this->snakeShortName(static::class);
        $relatedShort = $this->snakeShortName($related);

        $pivotTable       ??= $this->defaultPivotTable($parentShort, $relatedShort);
        $pivotForeignKey  ??= $parentShort . '_id';
        $pivotRelatedKey  ??= $relatedShort . '_id';

        return new Relations\BelongsToMany(
            $this,
            $related,
            $pivotTable,
            $pivotForeignKey,
            $pivotRelatedKey,
        );
    }

    /**
     * FK default: 'Categoria' → 'categoria_id'.
     *
     * @param class-string<Model> $class
     */
    protected function defaultForeignKey(string $class): string
    {
        return $this->snakeShortName($class) . '_id';
    }

    /**
     * Pivot default: 'Post' + 'Tag' → 'post_tag' (ordem alfabética).
     */
    protected function defaultPivotTable(string $a, string $b): string
    {
        $parts = [$a, $b];
        sort($parts);
        return implode('_', $parts);
    }

    /**
     * 'App\Models\ScheduledJob' → 'scheduled_job'
     */
    protected function snakeShortName(string $class): string
    {
        $short = (new \ReflectionClass($class))->getShortName();
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $short));
    }

    // ---------- Acesso dinâmico a relações ----------

    /**
     * Resolve relações via __get() — se o atributo não existir
     * em $attributes mas existir um método com o mesmo nome,
     * chama esse método e devolve o resultado da relação.
     *
     * Isto permite:
     *     $produto->categoria;    // BelongsTo::get()
     *     $produto->imagens;      // HasMany::get()
     *
     * Se quisermos o Relation object (para attach/sync/associate):
     *     $produto->categoria();  // chamada explícita de método
     */
    private function resolveRelation(string $name): mixed
    {
        if (!method_exists($this, $name)) {
            return null;
        }

        $result = $this->{$name}();

        if ($result instanceof Relations\Relation) {
            return $result->get();
        }

        return $result;
    }
}
