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

namespace Beaver\Database;

use PDO;
use PDOException;

/**
 * Beaver Framework Database connection wrapper.
 *
 * Supports SQLite, MySQL and PostgreSQL through PDO.
 *
 * Driver is chosen from (in order of priority):
 *   1. Explicit config passed to getInstance()
 *   2. DB_DRIVER from .env
 *   3. Default: 'sqlite' (zero-config for dev)
 *
 * SQLite stores everything in a single file. The path comes from
 * DB_DATABASE (or config.database_path). If the file does not exist,
 * it is created automatically.
 *
 * MySQL / PostgreSQL use host + port + dbname, from the .env.
 */
class Database
{
    private static ?self $instance = null;
    private static array $config = [];

    private PDO $pdo;
    private string $driver;

    private function __construct()
    {
     //  Load the database config file
        $configFile = dirname(__DIR__, 2) . '/config/database.php';
        $allConfig  = is_file($configFile) ? require $configFile : [];

     //  Which connection? (explicit > default in config > 'sqlite')
        $connectionName = self::$config['connection']
        ?? $allConfig['default']
        ?? 'sqlite';

     //  Get that connection's config
        $connection = self::$config['connection_config']
        ?? $allConfig['connections'][$connectionName]
        ?? throw new \RuntimeException(
            "Database connection not configured: {$connectionName}"
        );

     //  Any explicit config passed to getInstance() wins over the file
        $config = array_merge($connection, self::$config);

     // --- From here, same as before ---

        $driver  = $config['driver']   ?? 'sqlite';
        $host    = $config['host']     ?? '127.0.0.1';
        $dbname  = $config['database'] ?? 'beaver.sqlite';
        $user    = $config['username'] ?? '';
        $pass    = $config['password'] ?? '';
        $charset = $config['charset']  ?? 'utf8mb4';
        $port    = (int) ($config['port'] ?? $this->defaultPort($driver));
        $tz      = $config['timezone'] ?? null;

        $this->driver = $driver;

     // --- Build DSN per driver ---
        $dsn = match ($driver) {
              'sqlite' => 'sqlite:' . $this->resolveSqlitePath($dbname),
              'mysql'  => "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}",
              'pgsql'  => "pgsql:host={$host};port={$port};dbname={$dbname}",
              default  => throw new \RuntimeException("Unsupported database driver: {$driver}"),
        };

     // --- Base PDO options ---
        $options = $config['options'] ?? [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        ];

     // --- Driver-specific options ---
        if ($driver === 'mysql') {
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES {$charset}";
        }

     // --- Connect ---
        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);

            if ($driver === 'sqlite') {
                $this->pdo->exec('PRAGMA foreign_keys = ON;');
                $this->pdo->exec('PRAGMA journal_mode = WAL;');
            }

            if ($tz !== null && $driver !== 'sqlite') {
                $this->pdo->exec("SET time_zone = '{$tz}'");
            }
        } catch (PDOException $e) {
            throw new \RuntimeException(
                $this->translate('database.connection_failed', 'Database connection failed: ')
                . $e->getMessage(),
                previous: $e,
            );
        }
    }

    // ---------- Singleton ----------

    public static function getInstance(array $config = []): self
    {
        if ($config !== [] && self::$config !== $config) {
            self::$config = $config;
            self::$instance = null;
        }

        return self::$instance ??= new self();
    }

    public static function setConfig(array $config): void
    {
        self::$config = $config;
        self::$instance = null;
    }

    public static function getConfig(): array
    {
        return self::$config;
    }

    public static function reset(): void
    {
        self::$instance = null;
        self::$config = [];
    }

    // ---------- Accessors ----------

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function getDriverName(): string
    {
        return (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public function getServerVersion(): string
    {
        return (string) $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    public function quote(string $string, int $type = PDO::PARAM_STR): string
    {
        return $this->pdo->quote($string, $type);
    }

    public function getErrorInfo(): array
    {
        return $this->pdo->errorInfo();
    }

    public function __call(string $method, array $args)
    {
        if (method_exists($this->pdo, $method)) {
            return $this->pdo->$method(...$args);
        }
        throw new \BadMethodCallException("Method {$method} not found in Database or PDO");
    }

    // ---------- Queries ----------

    public function select(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function selectOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function insert(string $sql, array $params = []): string|false
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $this->pdo->lastInsertId();
    }

    public function update(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function delete(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function exec(string $sql): int
    {
        return $this->pdo->exec($sql);
    }

    public function prepare(string $sql, array $options = []): \PDOStatement
    {
        return $this->pdo->prepare($sql, $options);
    }

    public function query(string $sql): \PDOStatement
    {
        return $this->pdo->query($sql);
    }

    // ---------- Transactions ----------

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    // ---------- Schema introspection (per driver) ----------

    public function tableExists(string $tableName): bool
    {
        $driver = $this->getDriverName();

        [$sql, $params] = match ($driver) {
            'mysql'  => [
                "SELECT COUNT(*) AS count FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_name = ?",
                [$tableName],
            ],
            'pgsql'  => [
                "SELECT COUNT(*) AS count FROM information_schema.tables
                 WHERE table_schema = 'public' AND table_name = ?",
                [$tableName],
            ],
            'sqlite' => [
                "SELECT COUNT(*) AS count FROM sqlite_master
                 WHERE type = 'table' AND name = ?",
                [$tableName],
            ],
            default  => throw new \RuntimeException("Unsupported driver: {$driver}"),
        };

        $row = $this->selectOne($sql, $params);
        return $row !== null && (int) $row['count'] > 0;
    }

    /**
     * Returns column info as a normalized array:
     *   [ ['name' => 'id', 'type' => 'integer', 'nullable' => false, 'default' => null], ... ]
     *
     * Implementation differs per driver, but the output shape is the same.
     */
    public function getTableColumns(string $tableName): array
    {
        $driver = $this->getDriverName();

        return match ($driver) {
            'mysql'  => $this->columnsMysql($tableName),
            'pgsql'  => $this->columnsPgsql($tableName),
            'sqlite' => $this->columnsSqlite($tableName),
            default  => throw new \RuntimeException("Unsupported driver: {$driver}"),
        };
    }

    // ---------- Internals ----------

    private function env(string $key, mixed $default = null): mixed
    {
        if (function_exists('env')) {
            return env($key, $default);
        }
        $v = getenv($key);
        return $v === false ? $default : $v;
    }

    private function defaultPort(string $driver): int
    {
        return match ($driver) {
            'mysql' => 3306,
            'pgsql' => 5432,
            default => 0,
        };
    }

    private function resolveSqlitePath(string $path): string
    {
        // If it's an absolute path, use as-is.
        if (str_starts_with($path, '/') || preg_match('#^[A-Za-z]:[\\\\/]#', $path)) {
            $file = $path;
        } else {
            // Otherwise, treat as a filename inside storage/.
            $storage = function_exists('storage_path')
                ? storage_path()
                : dirname(__DIR__, 2) . '/storage';
            $file = rtrim($storage, '/') . '/' . $path;
        }

        // Ensure parent directory exists.
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $file;
    }

    private function translate(string $key, string $fallback): string
    {
        if (function_exists('__')) {
            try {
                return __($key);
            } catch (\Throwable) {
                // translator not booted yet — use fallback
            }
        }
        return $fallback;
    }

    // ---------- Column introspection per driver ----------

    private function columnsMysql(string $table): array
    {
        $rows = $this->select(
            "SELECT column_name AS name, data_type AS type,
                    is_nullable AS nullable, column_default AS `default`
             FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ?
             ORDER BY ordinal_position",
            [$table],
        );

        return array_map(fn ($r) => [
            'name'     => $r['name'],
            'type'     => $r['type'],
            'nullable' => $r['nullable'] === 'YES',
            'default'  => $r['default'],
        ], $rows);
    }

    private function columnsPgsql(string $table): array
    {
        $rows = $this->select(
            "SELECT column_name AS name, data_type AS type,
                    is_nullable AS nullable, column_default AS \"default\"
             FROM information_schema.columns
             WHERE table_schema = 'public' AND table_name = ?
             ORDER BY ordinal_position",
            [$table],
        );

        return array_map(fn ($r) => [
            'name'     => $r['name'],
            'type'     => $r['type'],
            'nullable' => $r['nullable'] === 'YES',
            'default'  => $r['default'],
        ], $rows);
    }

    private function columnsSqlite(string $table): array
    {
        // SQLite: use PRAGMA table_info — one row per column.
        $rows = $this->select("PRAGMA table_info(" . $this->quoteIdentifier($table) . ")");

        return array_map(fn ($r) => [
            'name'     => $r['name'],
            'type'     => strtolower((string) $r['type']),
            'nullable' => (int) $r['notnull'] === 0,
            'default'  => $r['dflt_value'],
        ], $rows);
    }

    private function quoteIdentifier(string $name): string
    {
        // Only allow safe identifier characters.
        if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            throw new \InvalidArgumentException("Invalid identifier: {$name}");
        }
        return '"' . $name . '"';
    }
}
