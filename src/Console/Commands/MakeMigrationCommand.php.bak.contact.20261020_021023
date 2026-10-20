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
 * Cria um novo ficheiro de migration.
 *
 * Uso:
 *   php beaver make:migration create_users_table
 *   php beaver make:migration create_users_table --schema="name:str,email:str?,age:int"
 *   php beaver make:migration create_users_table --schema="name,amount,user_id"
 *
 * Com --schema, as colunas são geradas automaticamente (formato compacto).
 * O tipo é inferido a partir do nome, ou forçado com name:type.
 */
class MakeMigrationCommand extends Command
{
    protected string $signature = 'make:migration {name} {--schema=} {--raw=} {--plugin=} {--dev} {--path=} {--lang=}';
    protected string $description = 'Create a new migration file';

    public function handle(): int
    {
        $slug = (string) $this->argument('name', '');

        if ($slug === '') {
            $this->error('Falta o nome da migration.');
            $this->line();
            $this->line('Uso: php beaver make:migration <nome> [--schema campo1,campo2,...]');
            $this->line('Ex:  php beaver make:migration create_users_table');
            $this->line('Ex:  php beaver make:migration create_users_table --schema="name:str,email:str?,age:int"');
            return self::INVALID;
        }

        $slug = $this->normalizeSlug($slug);

        if ($slug === '') {
            $this->error('Nome inválido após normalização.');
            return self::INVALID;
        }

        $fields = $this->parseSchema((string) $this->option('schema', ''));

        $rawSql = trim((string) $this->option('raw', ''));

        try {
            $migrationsDir = $this->migrationsPath();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        if ($rawSql !== '' && $fields !== []) {
            $this->warning('--raw e --schema são mutuamente exclusivos.');
            $this->line('Usa só um dos dois.');
            return self::INVALID;
        }

        // Verificar duplicados
        $existing = glob($migrationsDir . '/*_' . $slug . '.php') ?: [];

        if ($existing !== []) {
            $existingFile = basename($existing[0]);
            $baseName = basename($existing[0], '.php');

            $this->warning("Já existe uma migration com o mesmo nome:");
            $this->line("    {$existingFile}");
            $this->line();

    // Verificar se já correu na BD
            if ($this->hasRun($baseName)) {
                $this->warning("Esta migration já correu na BD.");
                $this->line();
                $this->line("Se recriares, ela fica registada com o conteúdo antigo.");
                $this->line("Para evitar problemas:");
                $this->line("  1. Faz rollback: php beaver migrate:rollback");
                $this->line("  2. Depois recria.");
                $this->line();
            }

            if (!$this->confirm('Deseja recriar?')) {
                $this->info('Cancelado.');
                return self::FAILURE;
            }


            // Apagar o(s) ficheiro(s) antigo(s)
            foreach ($existing as $file) {
                @unlink($file);
            }

            $this->line();
            $this->info('Ficheiro(s) antigo(s) apagado(s).');
        }

        $number = $this->nextNumber($migrationsDir);

        $filename = sprintf(
            '%s_%06d_%s.php',
            date('Y_m_d'),
            $number,
            $slug,
        );

        $path = $migrationsDir . '/' . $filename;

        if (!is_dir($migrationsDir)) {
            if (!mkdir($migrationsDir, 0775, true) && !is_dir($migrationsDir)) {
                $this->error("Não foi possível criar a pasta: {$migrationsDir}");
                return self::FAILURE;
            }
        }

        if (file_put_contents($path, $this->stub($slug, $fields, $rawSql)) === false) {
            $this->error("Não foi possível escrever: {$path}");
            return self::FAILURE;
        }

        $this->success("Migration criada: {$filename}");
        $this->line("     {$path}");

        if ($fields !== []) {
            $this->line();
            $this->line('     Campos:');
            foreach ($fields as $f) {
                $this->line('       - ' . $f);
            }
        }

        return self::SUCCESS;
    }

    private function hasRun(string $migration): bool
    {
        try {
            $db = \Beaver\Database\Database::getInstance();

            if (!$db->tableExists('migrations')) {
                return false;
            }

            $row = $db->selectOne(
                'SELECT COUNT(*) AS n FROM migrations WHERE migration = ?',
                [$migration],
            );

            return $row !== null && (int) $row['n'] > 0;
        } catch (\Throwable) {
            return false;   // se falhar (sem BD), assume que não correu
        }
    }

    // ---------- Internos ----------

    private function migrationsPath(): string
    {
        $plugin = $this->option('plugin');

        if (is_string($plugin) && $plugin !== '') {
              return $this->pluginMigrationsPath($plugin);
        }

        return rtrim($this->projectPath, '/') . '/database/migrations';
    }

/**
 * Caminho das migrations de um plugin.
 * Resolve o plugin via PluginManager e devolve a sua pasta migrations.
 */
  /**
 * Caminho das migrations de um plugin.
 *
 * Ordem de procura:
 *   1. --path=/caminho  → usa esse caminho diretamente
 *   2. --dev            → usa app.plugins.dev_path
 *   3. sem flags        → tenta app.plugins.path (prod) e depois app.plugins.dev_path
 */
    private function pluginMigrationsPath(string $slug): string
    {
        $app = \Beaver\Foundation\Application::getInstance();

        // 1. --path (explícito)
        $explicit = $this->option('path');
        if (is_string($explicit) && $explicit !== '') {
            $candidate = rtrim($explicit, '/') . '/' . $slug;

            if (is_dir($candidate)) {
                return $candidate . '/database/migrations';
            }

            throw new \RuntimeException(
                "Plugin '{$slug}' não encontrado em: {$candidate}"
            );
        }

        // 2. --dev
        if ($this->option('dev')) {
            $devPath = (string) $app->config('app.plugins.dev_path');

            if ($devPath !== '') {
                $candidate = rtrim($devPath, '/') . '/' . $slug;

                if (is_dir($candidate)) {
                    return $candidate . '/database/migrations';
                }
            }

            throw new \RuntimeException(
                "Plugin '{$slug}' não encontrado em dev_path: {$devPath}"
            );
        }

        // 3. Sem flags — tentar os dois sítios
        $candidates = [
        (string) $app->config('app.plugins.path'),        // prod
        (string) $app->config('app.plugins.dev_path'),    // dev
        ];

        foreach ($candidates as $base) {
            if ($base === '') {
                continue;
            }

            $candidate = rtrim($base, '/') . '/' . $slug;

            if (is_dir($candidate)) {
                return $candidate . '/database/migrations';
            }
        }

        throw new \RuntimeException(
            "Plugin '{$slug}' não encontrado. " .
            "Tenta --dev ou --path=/caminho/para/plugins."
        );
    }

    private function normalizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9]+/', '_', $slug) ?? '';
        return trim($slug, '_');
    }

    private function nextNumber(string $dir): int
    {
        if (!is_dir($dir)) {
            return 1;
        }

        $files = glob($dir . '/*.php') ?: [];
        $max = 0;

        foreach ($files as $file) {
            $base = basename($file, '.php');

            if (preg_match('/^\d{4}_\d{2}_\d{2}_(\d{6})_/', $base, $m)) {
                $n = (int) $m[1];
                if ($n > $max) {
                    $max = $n;
                }
            }
        }

        return $max + 1;
    }

    // ---------- --schema ----------

    /**
     * Interpreta --schema "name:str,email:str?,amount:dec"
     * e devolve uma lista de strings compactas.
     *
     * @return string[]
     */
    private function parseSchema(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $fields = [];

        foreach (explode(',', $raw) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            // Se tiver ':' → já tem tipo definido, manter como está
            if (str_contains($part, ':')) {
                $fields[] = $part;
                continue;
            }

            // Se for 'id' ou 'timestamps', manter
            if (in_array(strtolower($part), ['id', 'timestamps'], true)) {
                $fields[] = strtolower($part);
                continue;
            }

            // Senão, inferir tipo
            $type = $this->inferType($part);
            $fields[] = $part . ':' . $type;
        }

        return $fields;
    }

    /**
     * Infere o tipo a partir do nome da coluna.
     * Devolve o tipo em formato curto (str, int, dec, bool, date, time, datetime, json, uuid).
     */
    private function inferType(string $name): string
    {
        $name = strtolower(trim($name));

        if ($name === 'id' || str_ends_with($name, '_id')) {
            return 'int';
        }

        if (preg_match('/(amount|price|total|cost|salary|balance|fee)/', $name)) {
            return 'dec';
        }

        if (preg_match('/(count|qty|quantity|age|number|pump|stock)/', $name)) {
            return 'int';
        }

        // Datas: separar date/time/datetime
        if (preg_match('/(^|_)date($|_)/', $name)) {
            return 'date';
        }
        if (preg_match('/(^|_)time($|_)/', $name)) {
            return 'time';
        }
        if (preg_match('/(_at$|_on$|deadline|due|expires|datetime)/', $name)) {
            return 'datetime';
        }

        if (
            preg_match('/^(is_|has_|can_|should_)/', $name)
            || preg_match('/(_active$|_enabled$|_visible$|_deleted$|_paid$)/', $name)
        ) {
            return 'bool';
        }

        if (preg_match('/(metadata|payload|options|settings|config|data)/', $name)) {
            return 'json';
        }

        if (preg_match('/(description|notes|body|content|comment|text)/', $name)) {
            return 'text';
        }

        return 'str';
    }

    // ---------- stub ----------

    private function stub(string $slug, array $fields = [], string $rawSql = ''): string
    {
        $table = $this->tableFromSlug($slug);

    // Se for raw SQL, gerar stub minimalista
        if ($rawSql !== '') {
            return $this->rawStub($slug, $rawSql);
        }


        // Construir o array de campos
        $lines = ["'id',"];
        $hasTimestamps = false;
        foreach ($fields as $field) {
            if (strtolower(trim($field)) === 'timestamps') {
                $hasTimestamps = true;
            }
            $lines[] = "'" . $field . "',";
        }

        // Se o utilizador não declarou 'timestamps', adicionar por omissão
        if (!$hasTimestamps) {
            $lines[] = "'timestamps',";
        }

        // Indentar
        $arrayBody = implode("\n            ", $lines);

        return <<<PHP
<?php

use Beaver\\Database\\Migrations\\Migration;
use Beaver\\Database\\Migrations\\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('{$table}', [
            {$arrayBody}
        ]);
    }

    public function down(): void
    {
        Schema::drop('{$table}');
    }
};
PHP;
    }


/**
 * Stub para migrations com SQL cru.
 */
    private function rawStub(string $slug, string $rawSql): string
    {
        // Escapar o SQL para dentro da string PHP
        $escaped = addslashes($rawSql);

        // Tentar extrair o nome do objeto (trigger/procedure/view)
        $objectName = $this->extractObjectName($rawSql);

        $downSql = '';
        if ($objectName !== null) {
            if (stripos($rawSql, 'TRIGGER') !== false) {
                $downSql = "Schema::raw(\"DROP TRIGGER IF EXISTS {$objectName}\");";
            } elseif (stripos($rawSql, 'PROCEDURE') !== false) {
                $downSql = "Schema::raw(\"DROP PROCEDURE IF EXISTS {$objectName}\");";
            } elseif (stripos($rawSql, 'VIEW') !== false) {
                $downSql = "Schema::raw(\"DROP VIEW IF EXISTS {$objectName}\");";
            } elseif (stripos($rawSql, 'FUNCTION') !== false) {
                $downSql = "Schema::raw(\"DROP FUNCTION IF EXISTS {$objectName}\");";
            }
        }

        if ($downSql === '') {
            $downSql = "// TODO: reverter (SQL cru)\n        // Schema::raw(\"...\");";
        }

        return <<<PHP
<?php

use Beaver\\Database\\Migrations\\Migration;
use Beaver\\Database\\Migrations\\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::raw('{$escaped}');
    }

    public function down(): void
    {
        {$downSql}
    }
};
PHP;
    }

    /**
     * Extrai o nome do objeto do SQL (trigger, procedure, view, function).
     */
    private function extractObjectName(string $sql): ?string
    {
        // CREATE TRIGGER nome ... / CREATE PROCEDURE nome ... / CREATE VIEW nome ...
        if (preg_match('/CREATE\\s+(?:OR\\s+REPLACE\\s+)?(TRIGGER|PROCEDURE|VIEW|FUNCTION)\\s+[`"]?([\\w_]+)[`"]?/i', $sql, $m)) {
            return $m[2];
        }
        return null;
    }

    private function tableFromSlug(string $slug): string
    {
        if (preg_match('/^create_(.+?)_table$/', $slug, $m)) {
            return $m[1];
        }

        return $slug;
    }
}
