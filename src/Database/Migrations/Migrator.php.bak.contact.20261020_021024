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
 * Corre as migrations pendentes.
 *
 * Aceita um path (string) ou vários (array).
 * Lê os ficheiros *.php de cada path, verifica quais já correram
 * (tabela `migrations`), e corre os que faltam.
 */
class Migrator
{
    /** @var string[] */
    private array $paths;

    public function __construct(string|array $migrationsPath)
    {
        $paths = is_array($migrationsPath) ? $migrationsPath : [$migrationsPath];

        $this->paths = array_values(array_filter(
            array_map(fn ($p) => rtrim((string) $p, '/'), $paths),
            fn ($p) => $p !== '' && is_dir($p),
        ));
    }

    /** @return string[] Nomes das migrations que correram */
    public function run(): array
    {
        $this->ensureMigrationsTable();

        $ran       = $this->alreadyRan();
        $nextBatch = $this->nextBatchNumber();
        $executed  = [];

        foreach ($this->allMigrationFiles() as $file) {
            $name = basename($file, '.php');

            if (in_array($name, $ran, true)) {
                continue;
            }

            $this->runOne($file, $name, $nextBatch);
            $executed[] = $name;
        }

        return $executed;
    }

    /** @return string[] Nomes das migrations revertidas */
    public function rollback(int $steps = 1): array
    {
        $this->ensureMigrationsTable();

        $db = Database::getInstance();

        $batches = $db->select(
            'SELECT DISTINCT batch FROM migrations ORDER BY batch DESC LIMIT ' . (int) $steps,
        );

        if ($batches === []) {
            return [];
        }

        $batchNumbers = array_column($batches, 'batch');
        $placeholders = implode(',', array_fill(0, count($batchNumbers), '?'));

        $rows = $db->select(
            "SELECT migration FROM migrations WHERE batch IN ({$placeholders}) ORDER BY migration DESC",
            $batchNumbers,
        );

        $reverted = [];

        foreach ($rows as $row) {
            $name = $row['migration'];
            $file = $this->findMigrationFile($name);

            if ($file === null) {
                continue;
            }

            $migration = require $file;
            if ($migration instanceof Migration) {
                $migration->down();
            }

            $db->delete('DELETE FROM migrations WHERE migration = ?', [$name]);
            $reverted[] = $name;
        }

        return $reverted;
    }

    /** @return array<int, array{migration:string, ran:bool}> */
    public function status(): array
    {
        $this->ensureMigrationsTable();

        $ran = $this->alreadyRan();
        $out = [];

        foreach ($this->allMigrationFiles() as $file) {
            $name = basename($file, '.php');
            $out[] = [
                'migration' => $name,
                'ran'       => in_array($name, $ran, true),
            ];
        }

        return $out;
    }

    // ---------- Internos ----------

    private function ensureMigrationsTable(): void
    {
        $driver = Database::getInstance()->getDriverName();

        $idType = match ($driver) {
            'mysql'  => 'INT AUTO_INCREMENT PRIMARY KEY',
            'pgsql'  => 'SERIAL PRIMARY KEY',
            'sqlite' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
        };

        Database::getInstance()->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id {$idType},
                migration VARCHAR(255) NOT NULL UNIQUE,
                batch INT NOT NULL DEFAULT 1,
                ran_at VARCHAR(32) NOT NULL
            )
        ");
    }

    private function alreadyRan(): array
    {
        $rows = Database::getInstance()->select('SELECT migration FROM migrations');
        return array_column($rows, 'migration');
    }

    private function nextBatchNumber(): int
    {
        $row = Database::getInstance()->selectOne('SELECT MAX(batch) AS b FROM migrations');
        return (int) ($row['b'] ?? 0) + 1;
    }

    /**
     * Devolve todos os ficheiros *.php de todos os paths,
     * ordenados por nome, sem duplicados.
     *
     * @return string[]
     */
    private function allMigrationFiles(): array
    {
        $files = [];

        foreach ($this->paths as $path) {
            $found = glob($path . '/*.php') ?: [];
            $files = array_merge($files, $found);
        }

        // Remover duplicados (se dois paths tiverem o mesmo ficheiro)
        $files = array_values(array_unique($files));

        sort($files);

        return $files;
    }

    /**
     * Procura o ficheiro de uma migration pelo nome (sem .php)
     * em todos os paths.
     */
    private function findMigrationFile(string $name): ?string
    {
        foreach ($this->paths as $path) {
            $candidate = $path . '/' . $name . '.php';
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function runOne(string $file, string $name, int $batch): void
    {
        $migration = require $file;

        if (!$migration instanceof Migration) {
            throw new \RuntimeException(
                "Migration inválida (deve devolver um objeto Migration): {$file}"
            );
        }

        $migration->up();

        Database::getInstance()->insert(
            'INSERT INTO migrations (migration, batch, ran_at) VALUES (?, ?, ?)',
            [$name, $batch, date('Y-m-d H:i:s')],
        );
    }
}
