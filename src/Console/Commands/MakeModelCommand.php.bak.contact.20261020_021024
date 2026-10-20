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

namespace Beaver\Console\Commands;

use Beaver\Console\Command;

/**
 * Cria um novo Model.
 *
 * Uso:
 *   php beaver make:model Post
 *   php beaver make:model Post --plugin=blog
 *   php beaver make:model Post --plugin=blog --migration
 *   php beaver make:model Post --table=custom_posts
 *   php beaver make:model Post --fillable="title,body,status"
 *
 * Estrutura gerada:
 *   class Post extends Model
 *   {
 *       protected static string $table = 'posts';
 *       protected array $fillable = [];
 *   }
 */
class MakeModelCommand extends Command
{
    protected string $signature = 'make:model {name} {--plugin=} {--dev} {--path=} {--table=} 
   {--fillable=} {--hidden=} {--casts=} {--no-timestamps} {--migration} {--schema=} 
   {--from-table=} {--from-file=}';
    protected string $description = 'Create a new model';

    public function handle(): int
    {
        $raw  = (string) $this->argument('name', '');
        $name = $this->studly($raw);

        if ($raw === '' || $name === '') {
            $this->error('Falta o nome do Model.');
            $this->line();
            $this->line('Uso: php beaver make:model <nome> [opções]');
            $this->line('Ex:  php beaver make:model Post');
            $this->line('Ex:  php beaver make:model Post --plugin=blog --migration');
            $this->line('Ex:  php beaver make:model Post --plugin=blog --from-table=posts');
            return self::INVALID;
        }

    // ---------- 1. Resolver tabela, fillable, casts ----------

        $fromTable = (string) $this->option('from-table', '');

        $fromFile  = (string) $this->option('from-file', '');

        if ($fromTable !== '') {
            // Modo --from-table: lê a BD
            try {
                [$table, $fillable, $casts] = $this->readFromTable($fromTable);
            } catch (\Throwable $e) {
                $this->error($e->getMessage());
                return self::FAILURE;
            }

            // --fillable explícito ganha sobre o lido da BD
            $fillableRaw = (string) $this->option('fillable', '');
            if ($fillableRaw !== '') {
                $fillable = $this->parseList($fillableRaw);
            }

            // --casts explícito ganha sobre a inferência
            $castsRaw = (string) $this->option('casts', '');
            if ($castsRaw !== '') {
                $casts = $this->parseCasts($castsRaw);
            }

            $this->info("Lido da tabela '{$fromTable}':");
            $this->line("     Colunas: " . count($fillable));
            $this->line("     Casts:   " . count($casts));
        } elseif ($fromFile !== '') {
            // Modo --from-file: lê a migration
            try {
                [$table, $fillable, $casts] = $this->readFromFile($fromFile);
            } catch (\Throwable $e) {
                $this->error($e->getMessage());
                return self::FAILURE;
            }

            // --fillable explícito ganha sobre o lido da migration
            $fillableRaw = (string) $this->option('fillable', '');
            if ($fillableRaw !== '') {
                $fillable = $this->parseList($fillableRaw);
            }

            // --casts explícito ganha sobre a inferência
            $castsRaw = (string) $this->option('casts', '');
            if ($castsRaw !== '') {
                $casts = $this->parseCasts($castsRaw);
            }

            $this->info("Lido da migration '{$fromFile}':");
            $this->line("     Colunas: " . count($fillable));
            $this->line("     Casts:   " . count($casts));
        } else {
            // Modo normal: --table + --schema/--fillable/--casts
            $table = (string) $this->option('table', '');
            if ($table === '') {
                $table = $this->pluralize($this->snake($name));
            }

            // --fillable explícito ganha sobre o --schema
            $fillableRaw = (string) $this->option('fillable', '');

            if ($fillableRaw !== '') {
                $fillable = $this->parseList($fillableRaw);
            } else {
                $fillable = $this->fillableFromSchema((string) $this->option('schema', ''));
            }

            // --casts explícito ganha sobre a inferência do --schema
            $castsRaw = (string) $this->option('casts', '');

            if ($castsRaw !== '') {
                $casts = $this->parseCasts($castsRaw);
            } else {
                $casts = $this->castsFromSchema((string) $this->option('schema', ''));
            }
        }

    // ---------- 2. Hidden e timestamps (comuns) ----------

        $hidden     = $this->parseList((string) $this->option('hidden', ''));
        $timestamps = !$this->option('no-timestamps');

    // ---------- 3. Resolver destino ----------

        try {
            [$namespace, $targetDir] = $this->resolveTarget();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $path = rtrim($targetDir, '/') . '/' . $name . '.php';

    // ---------- 4. Verificar duplicado ----------

        if (is_file($path)) {
            $this->warning("Já existe um Model em: {$path}");
            $this->line();

            if (!$this->confirm('Deseja recriar?')) {
                $this->info('Cancelado.');
                return self::FAILURE;
            }

            @unlink($path);
            $this->line();
            $this->info('Ficheiro antigo apagado.');
        }

    // ---------- 5. Criar pasta ----------

        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                $this->error("Não foi possível criar a pasta: {$targetDir}");
                return self::FAILURE;
            }
        }

    // ---------- 6. Escrever ----------

        $stub = $this->stub(
            $name,
            $namespace,
            $table,
            $fillable,
            $hidden,
            $casts,
            $timestamps,
        );

        if (file_put_contents($path, $stub) === false) {
            $this->error("Não foi possível escrever: {$path}");
            return self::FAILURE;
        }

    // ---------- 7. Output ----------

        $this->success("Model criado: {$name}");
        $this->line("     {$path}");
        $this->line();
        $this->line("     Tabela:    {$table}");
        $this->line("     Namespace: {$namespace}");

        if ($fillable !== []) {
            $this->line('     Fillable:  ' . implode(', ', $fillable));
        }
        if ($hidden !== []) {
            $this->line('     Hidden:    ' . implode(', ', $hidden));
        }
        if ($casts !== []) {
            $castsStr = [];
            foreach ($casts as $f => $t) {
                $castsStr[] = "{$f}:{$t}";
            }
            $this->line('     Casts:     ' . implode(', ', $castsStr));
        }

    // ---------- 8. Migration opcional ----------

        if ($this->option('migration')) {
            $this->line();
            $this->info('A criar migration...');

            $migration = new MakeMigrationCommand();
            $migration->setProjectPath($this->projectPath);

            $migrationArgs = [
            "create_{$table}_table",
            '--schema=' . (string) $this->option('schema', ''),
            ];

            $plugin = $this->option('plugin');
            if (is_string($plugin) && $plugin !== '') {
                $migrationArgs[] = '--plugin=' . $plugin;
            }

            if ($this->option('dev')) {
                $migrationArgs[] = '--dev';
            }

            $explicitPath = $this->option('path');
            if (is_string($explicitPath) && $explicitPath !== '') {
                $migrationArgs[] = '--path=' . $explicitPath;
            }

            $migration->parse($migrationArgs);
            $code = $migration->handle();

            if ($code !== self::SUCCESS) {
                $this->warning('Model criado, mas a migration falhou.');
                return $code;
            }
        }

        return self::SUCCESS;
    }

    /**
 * Extrai os nomes dos campos de um schema compacto.
 *
 * "title:str,body:text?,status:str=draft"
 *   → ['title', 'body', 'status']
 *
 * Ignora:
 *   - 'id' (não é fillable)
 *   - 'timestamps' (não é coluna)
 *   - 'created_at', 'updated_at' (não são fillable)
 */
    private function fillableFromSchema(string $schema): array
    {
        $schema = trim($schema);
        if ($schema === '') {
            return [];
        }

        $ignore = ['id', 'timestamps', 'created_at', 'updated_at'];
        $fields = [];

        foreach (explode(',', $schema) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            // Extrair o nome (antes do ':')
            $name = explode(':', $part, 2)[0];
            $name = trim($name);

            if (in_array(strtolower($name), $ignore, true)) {
                continue;
            }

            $fields[] = $name;
        }

        return $fields;
    }

    // ---------- Internos ----------

    /**
     * Resolve o destino (namespace + pasta).
     *
     * Ordem:
     *   1. --path  → /caminho/src/Models
     *   2. --plugin (sem --dev) → plugin/src/Models
     *   3. --plugin --dev      → dev_path/<plugin>/src/Models
     *   4. sem flags           → app/Models do projeto
     */
    private function resolveTarget(): array
    {
        $plugin = $this->option('plugin');

        // 1. --path explícito
        $explicit = $this->option('path');
        if (is_string($explicit) && $explicit !== '') {
            $base = rtrim($explicit, '/');
            $dir  = $base . '/src/Models';

            // Tentar descobrir o namespace a partir do composer.json do plugin
            $namespace = $this->namespaceFromPlugin($base);

            return [$namespace . '\\Models', $dir];
        }

        // 2. --plugin
        if (is_string($plugin) && $plugin !== '') {
            $base = $this->resolvePluginPath($plugin);
            $namespace = 'Beaver\\Plugins\\' . $this->studly($plugin);

            return [$namespace . '\\Models', $base . '/src/Models'];
        }

        // 3. Framework (app/Models)
        $dir = rtrim($this->projectPath, '/') . '/app/Models';
        return ['App\\Models', $dir];
    }

    /**
     * Resolve o caminho do plugin.
     * Usa o PluginManager para descobrir.
     */
    private function resolvePluginPath(string $slug): string
    {
        try {
            $app = \Beaver\Foundation\Application::getInstance();
            $pm  = $app->make(\Beaver\Plugin\PluginManager::class)->discover();

            $manifests = $pm->manifests();

            if (!isset($manifests[$slug])) {
                throw new \RuntimeException("Plugin '{$slug}' não encontrado.");
            }

            return rtrim($manifests[$slug]['path'], '/');
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                "Erro ao resolver o plugin '{$slug}': " . $e->getMessage(),
            );
        }
    }

    /**
     * Tenta descobrir o namespace do plugin a partir do composer.json.
     * Fallback: assume Beaver\Plugins\<Studly>.
     */
    private function namespaceFromPlugin(string $pluginPath): string
    {
        $composerPath = $pluginPath . '/composer.json';

        if (is_file($composerPath)) {
            $composer = json_decode((string) file_get_contents($composerPath), true);
            $psr4 = $composer['autoload']['psr-4'] ?? [];

            foreach ($psr4 as $ns => $dir) {
                // Primeira chave, tira o \\ final
                return rtrim($ns, '\\');
            }
        }

        return 'Beaver\\Plugins\\' . $this->studly(basename($pluginPath));
    }

    private function parseList(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $items = [];
        foreach (explode(',', $raw) as $part) {
            $part = trim($part);
            if ($part !== '') {
                $items[] = $part;
            }
        }

        return $items;
    }

    private function studly(string $name): string
    {
        $name = str_replace(['_', '-'], ' ', trim($name));
        $name = ucwords($name);
        return str_replace(' ', '', $name);
    }

    private function snake(string $name): string
    {
        $name = preg_replace('/(?<!^)[A-Z]/', '_$0', $name) ?? '';
        return strtolower($name);
    }

    /**
     * Pluralização simples:
     *   category → categories
     *   box      → boxes
     *   post     → posts
     */
    private function pluralize(string $word): string
    {
        $word = strtolower($word);

        // -y (consoante + y) → -ies
        if (str_ends_with($word, 'y') && !preg_match('/[aeiou]y$/', $word)) {
            return substr($word, 0, -1) . 'ies';
        }

        // -s, -x, -z, -ch, -sh → -es
        if (preg_match('/(s|x|z|ch|sh)$/', $word)) {
            return $word . 'es';
        }

        // default: +s
        return $word . 's';
    }

    // ---------- stub ----------

    private function stub(
        string $name,
        string $namespace,
        string $table,
        array $fillable,
        array $hidden = [],
        array $casts = [],
        bool $timestamps = true,
    ): string {
      // ---------- fillable ----------
        if ($fillable !== []) {
            $fillableCode = "    protected array \$fillable = [\n";
            foreach ($fillable as $f) {
                $fillableCode .= "        '" . $f . "',\n";
            }
            $fillableCode .= "    ];";
        } else {
            $fillableCode = "    protected array \$fillable = [\n        // 'title',\n        // 'body',\n    ];";
        }

      // ---------- hidden ----------
        $hiddenCode = '';
        if ($hidden !== []) {
            $hiddenCode = "\n\n    protected array \$hidden = [\n";
            foreach ($hidden as $h) {
                $hiddenCode .= "        '" . $h . "',\n";
            }
            $hiddenCode .= "    ];";
        }

      // ---------- casts ----------
        $castsCode = '';
        if ($casts !== []) {
            $castsCode = "\n\n    protected array \$casts = [\n";
            foreach ($casts as $field => $cast) {
                $castsCode .= "        '" . $field . "' => '" . $cast . "',\n";
            }
            $castsCode .= "    ];";
        }

      // ---------- timestamps ----------
        $timestampsCode = '';
        if (!$timestamps) {
            $timestampsCode = "\n\n    protected static bool \$timestamps = false;";
        }

        return <<<PHP
<?php

namespace {$namespace};

use Beaver\\Database\\Model\\Model;

class {$name} extends Model
{
    protected static string \$table = '{$table}';

{$fillableCode}{$hiddenCode}{$castsCode}{$timestampsCode}
}
PHP;
    }


    /**
 * Lê a estrutura de uma tabela da BD.
 *
 * Ignora: id, created_at, updated_at
 *
 * @return array{0:string, 1:string[], 2:array<string,string>}
 */
    private function readFromTable(string $table): array
    {
        $db = \Beaver\Database\Database::getInstance();

        if (!$db->tableExists($table)) {
            throw new \RuntimeException("A tabela '{$table}' não existe na BD.");
        }

        $columns  = $db->getTableColumns($table);
        $ignore   = ['id', 'created_at', 'updated_at'];
        $fillable = [];
        $casts    = [];

        foreach ($columns as $col) {
            $name = $col['name'];

            if (in_array(strtolower($name), $ignore, true)) {
                continue;
            }

            $fillable[] = $name;

            $cast = $this->castFromColumn($name, $col['type'] ?? '');
            if ($cast !== null) {
                $casts[$name] = $cast;
            }
        }

        return [$table, $fillable, $casts];
    }

    /**
 * Infere um cast a partir do tipo SQL da coluna + nome.
 */
    private function castFromColumn(string $name, string $sqlType): ?string
    {
        $name = strtolower($name);

        // Pelo nome (mais fiável em SQLite)
        if (
            preg_match('/^(is_|has_|can_|should_)/', $name)
            || preg_match('/(_active|_enabled|_visible|_deleted|_paid)$/', $name)
        ) {
            return 'bool';
        }

        if (preg_match('/(metadata|payload|options|settings|config|data)$/', $name)) {
            return 'array';
        }

        if (
            str_ends_with($name, '_at')
            || str_ends_with($name, '_on')
            || str_contains($name, 'date')
            || str_contains($name, 'time')
        ) {
            return 'datetime';
        }

        if (preg_match('/(amount|price|total|cost|salary|balance|fee)$/', $name)) {
            return 'float';
        }

        // Pelo tipo SQL
        $sql = strtolower(trim($sqlType));
        $sql = preg_replace('/\(.*\)/', '', $sql);   // 'varchar(255)' → 'varchar'

        return match ($sql) {
            'int', 'integer', 'bigint', 'smallint', 'tinyint', 'serial' => 'int',
            'decimal', 'numeric', 'float', 'double', 'real'             => 'float',
            'bool', 'boolean'                                           => 'bool',
            'json', 'jsonb'                                             => 'array',
            'datetime', 'timestamp', 'timestamptz'                      => 'datetime',
            'date'                                                      => 'date',
            default                                                     => null,
        };
    }

    /**
 * Infere casts a partir do schema compacto.
 */
    private function castsFromSchema(string $schema): array
    {
        $schema = trim($schema);
        if ($schema === '') {
            return [];
        }

        $casts = [];

        foreach (explode(',', $schema) as $part) {
            $part = trim($part);
            if ($part === '' || !str_contains($part, ':')) {
                continue;
            }

            [$name, $rest] = explode(':', $part, 2);
            $name = trim($name);

            $type = preg_replace('/[\?\(=].*/', '', $rest);
            $type = strtolower(trim($type));

            $cast = match ($type) {
                'bool', 'boolean'   => 'bool',
                'int', 'integer'    => 'int',
                'dec', 'decimal',
                'float', 'double'   => 'float',
                'date'              => 'date',
                'datetime',
                'timestamp'         => 'datetime',
                'json'              => 'array',
                default             => null,
            };

            if ($cast !== null) {
                $casts[$name] = $cast;
            }
        }

        return $casts;
    }


    /**
 * Parse "--casts=published_at:datetime,is_active:bool"
 *   → ['published_at' => 'datetime', 'is_active' => 'bool']
 */
    private function parseCasts(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $casts = [];

        foreach (explode(',', $raw) as $part) {
            $part = trim($part);
            if ($part === '' || !str_contains($part, ':')) {
                continue;
            }

            [$field, $type] = explode(':', $part, 2);
            $casts[trim($field)] = trim($type);
        }

        return $casts;
    }

    /**
 * Lê uma migration e extrai [table, fillable[], casts[]].
 *
 * Procura o ficheiro em:
 *   - framework/database/migrations/*_<slug>.php
 *   - <plugin>/database/migrations/*_<slug>.php (se --plugin)
 *
 * @return array{0:string, 1:string[], 2:array<string,string>}
 */
    private function readFromFile(string $slug): array
    {
        $slug = $this->normalizeSlug($slug);

        $paths = $this->migrationSearchPaths();
        $file  = null;

        foreach ($paths as $path) {
            $matches = glob($path . '/*_' . $slug . '.php') ?: [];
            if ($matches !== []) {
                $file = $matches[0];
                break;
            }
        }

        if ($file === null) {
            throw new \RuntimeException(
                "Migration '{$slug}' não encontrada. Procurei em: " . implode(', ', $paths)
            );
        }

        $content = (string) file_get_contents($file);

        // Extrair o Schema::create('table', [ ... ])
        if (
            !preg_match(
                "/Schema::create\(\s*['\"]([^'\"]+)['\"]\s*,\s*\[(.*?)\]\s*\)/s",
                $content,
                $matches
            )
        ) {
            throw new \RuntimeException(
                "Não consegui extrair o Schema::create() da migration '{$slug}'."
            );
        }

        $table = $matches[1];
        $body  = $matches[2];

        $fillable = [];
        $casts    = [];

        // Procura strings 'name:type' dentro do array
        if (preg_match_all("/['\"]([a-z_]+:[^'\"]+)['\"]/", $body, $fieldMatches)) {
            foreach ($fieldMatches[1] as $field) {
                if (in_array(strtolower($field), ['id', 'timestamps'], true)) {
                    continue;
                }

                $name = explode(':', $field, 2)[0];
                $fillable[] = $name;

                $cast = $this->castFromFieldString($field);
                if ($cast !== null) {
                    $casts[$name] = $cast;
                }
            }
        }

        return [$table, $fillable, $casts];
    }

/**
 * Devolve os paths onde procurar migrations.
 *
 * @return string[]
 */
    private function migrationSearchPaths(): array
    {
        $paths = [];

        // 1. Framework
        $frameworkPath = rtrim($this->projectPath, '/') . '/database/migrations';
        if (is_dir($frameworkPath)) {
            $paths[] = $frameworkPath;
        }

        // 2. Plugin (se --plugin)
        $plugin = $this->option('plugin');
        if (is_string($plugin) && $plugin !== '') {
            try {
                $base = $this->resolvePluginPath($plugin);
                $pluginPath = $base . '/database/migrations';
                if (is_dir($pluginPath)) {
                    $paths[] = $pluginPath;
                }
            } catch (\Throwable) {
                // ignora
            }
        }

        return $paths;
    }

/**
 * Infere um cast a partir da string 'name:type'.
 * Ex: 'published_at:datetime?' → 'datetime'
 */
    private function castFromFieldString(string $field): ?string
    {
        $rest = explode(':', $field, 2)[1] ?? '';

        // Extrair só o tipo (antes de '(' e '?' e '=')
        $type = preg_replace('/[\?\(=].*/', '', $rest);
        $type = strtolower(trim($type));

        return match ($type) {
            'bool', 'boolean' => 'bool',
            'int', 'integer'  => 'int',
            'dec', 'decimal',
            'float', 'double' => 'float',
            'date'            => 'date',
            'datetime',
            'timestamp'       => 'datetime',
            'json'            => 'array',
            default           => null,
        };
    }

/**
 * Normaliza um slug: 'CreatePostsTable' → 'create_posts_table'.
 */
    private function normalizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9]+/', '_', $slug) ?? '';
        return trim($slug, '_');
    }
}
