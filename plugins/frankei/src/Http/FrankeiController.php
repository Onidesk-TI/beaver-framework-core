<?php

declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Http;

use Beaver\Http\Request;
use Beaver\Http\Response;
use Beaver\Foundation\Application;
use Beaver\Plugins\Frankei\Frankei\FrankeiCache;
use Beaver\Plugins\Frankei\Frankei\FrankeiRunner;
use Beaver\Plugins\Frankei\Frankei\ScanContext;
use Beaver\Plugins\Frankei\Frankei\CodeScannerRegistry;
use Beaver\Plugins\Frankei\Frankei\DotEnvReader;
use Beaver\Plugins\Frankei\Frankei\ScannerRegistry;

class FrankeiController
{
    private ScannerRegistry $registry;
    private FrankeiRunner $runner;
    private FrankeiCache $cache;

    public function __construct()
    {
        $this->registry = new ScannerRegistry();
        $this->runner   = new FrankeiRunner($this->registry);
        $this->cache    = new FrankeiCache();
    }

    /** GET /api/frankei/scanners */
    public function scanners(): Response
    {
        return Response::json(['scanners' => $this->registry->metadata()]);
    }

    /**
     * POST /api/frankei/scan
     * Body: { connection_id, database, tables?, scanners?, force? }
     */
    public function scan(Request $request): Response
    {
        $body = $request->body ?? [];

        $connectionId = (string) ($body['connection_id'] ?? '');
        $database     = (string) ($body['database']      ?? '');
        $tables       = (array)  ($body['tables']        ?? []);
        $scannerIds   = (array)  ($body['scanners']      ?? []);
        $force        = (bool)   ($body['force']         ?? false);

        if ($connectionId === '') {
            return Response::json(['error' => 'connection_id é obrigatório'], 422);
        }

        // Cache (só quando se correm TODOS os scanners)
        $useCache = !$force && $scannerIds === [];
        if ($useCache) {
            $cached = $this->cache->get($connectionId, $database, $tables);
            if ($cached !== null) {
                $cached['_meta']['cached'] = true;
                return Response::json($cached);
            }
        }

        try {
            [$pdo, $driver] = $this->openConnection($connectionId, $database);
        } catch (\Throwable $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }

        $ctx = new ScanContext(
            pdo:      $pdo,
            driver:   $driver,
            database: $database,
            tables:   $tables,
            mode:     'readonly',
        );

        try {
            $result = $this->runner->run($ctx, $scannerIds);
        } catch (\Throwable $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }

        $result['_meta'] = [
            'connection_id' => $connectionId,
            'database'      => $database,
            'tables'        => $tables,
            'cached'        => false,
            'scanned_at'    => date('c'),
        ];

        if ($useCache) {
            $this->cache->put($connectionId, $database, $tables, $result);
        }

        return Response::json($result);
    }

    /** POST /api/frankei/invalidate — invalida cache de uma ligação/db */
    public function invalidate(Request $request): Response
    {
        $body = $request->body ?? [];
        $connectionId = (string) ($body['connection_id'] ?? '');
        $database     = (string) ($body['database']      ?? '');

        if ($connectionId === '') {
            return Response::json(['error' => 'connection_id é obrigatório'], 422);
        }

        $this->cache->invalidate($connectionId, $database);
        return Response::json(['ok' => true]);
    }

    /**
     * POST /api/frankei/scan-path
     * Body: { path: "/var/www/projeto" }
     *
     * Lê o .env do projeto e analisa todas as BDs encontradas.
     */
    public function scanPath(Request $request): Response
    {
        $body = $request->body ?? [];
        $path = trim((string) ($body['path'] ?? ''));

        if ($path === '' || !is_dir($path)) {
            return Response::json(['error' => 'path inválido'], 422);
        }

        // ── 1. Tentar ler do .env ──
        $databases = DotEnvReader::readDatabases($path);

        // ── 2. Fallback: procurar ficheiros de BD diretamente ──
        if (empty($databases)) {
            $databases = self::findDatabaseFiles($path);
        }

        // ── 3. Nada encontrado → resposta graciosa (não é erro fatal) ──
        if (empty($databases)) {
            return Response::json([
                'ok'       => true,
                'skipped'  => true,
                'path'     => $path,
                'motivo'   => 'Sem base de dados nesta pasta (.env / *.sqlite / *.db / *.sql)',
                'dbs'      => [],
                'findings' => [],
                'errors'   => [],
                'stats'    => [
                    'databases' => 0,
                    'findings'  => 0,
                    'duration'  => 0,
                ],
            ]);
        }

        $allFindings = [];
        $byDatabase  = [];
        $errors      = [];
        $t0          = microtime(true);

        foreach ($databases as $db) {
            $dbKey = $db['key'];

            try {
                $pdo = new \PDO('sqlite:' . $db['path'], null, null, [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_TIMEOUT            => 5,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                ]);
            } catch (\Throwable $e) {
                $errors[] = ['database' => $dbKey, 'message' => $e->getMessage()];
                continue;
            }

            $ctx = new ScanContext(
                pdo:      $pdo,
                driver:   'sqlite',
                database: $db['path'],
                tables:   [],
                mode:     'readonly',
            );

            try {
                $result = $this->runner->run($ctx, []);
            } catch (\Throwable $e) {
                $errors[] = ['database' => $dbKey, 'message' => $e->getMessage()];
                continue;
            }

            // Anotar findings com a BD de origem
            foreach ($result['findings'] as &$f) {
                $f['database_key']   = $dbKey;
                $f['database_label'] = $db['label'];
            }
            unset($f);

            $allFindings = array_merge($allFindings, $result['findings']);

            $byDatabase[$dbKey] = [
                'label'    => $db['label'],
                'path'     => $db['path'],
                'stats'    => $result['stats'],
                'findings' => count($result['findings']),
            ];
        }

        $elapsed = (int) round((microtime(true) - $t0) * 1000);

        // Stats agregadas
        $bySeverity = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0, 'info' => 0];
        $byCategory = [];
        foreach ($allFindings as $f) {
            $sev = $f['severity'] ?? 'info';
            $cat = $f['category'] ?? 'other';
            if (isset($bySeverity[$sev])) $bySeverity[$sev]++;
            $byCategory[$cat] = ($byCategory[$cat] ?? 0) + 1;
        }

        return Response::json([
            'path'        => $path,
            'databases'   => array_values($databases),
            'by_database' => $byDatabase,
            'findings'    => $allFindings,
            'stats'       => [
                'total'       => count($allFindings),
                'by_severity' => $bySeverity,
                'by_category' => $byCategory,
                'elapsed_ms'  => $elapsed,
                'databases'   => count($databases),
            ],
            'errors'      => $errors,
            'scanned_at'  => date('c'),
        ]);
    }


    /**
     * POST /api/frankei/scan-code
     * Body: { path, scanners?: [] }
     *
     * Analisa o código PHP do projeto (segurança, passwords, etc.)
     */
    public function scanCode(Request $request): Response
    {
        $body = $request->body ?? [];
        $path = trim((string) ($body['path'] ?? ''));

        if ($path === '' || !is_dir($path)) {
            return Response::json(['error' => 'path inválido'], 422);
        }

        $scannerIds = (array) ($body['scanners'] ?? []);
        $options    = (array) ($body['options']  ?? []);

        try {
            $registry = new CodeScannerRegistry();
            $result   = $registry->run($path, $scannerIds, $options);
        } catch (\Throwable $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }

        return Response::json([
            'path'       => $path,
            'findings'   => $result['findings'],
            'stats'      => $result['stats'],
            'errors'     => $result['errors'],
            'scanners'   => $registry->metadata(),
            'scanned_at' => date('c'),
        ]);
    }

    /** @return array{0: \PDO, 1: string} */
    private function openConnection(string $connectionId, string $database): array
    {
        $basePath   = Application::getInstance()->basePath;
        $pluginPath = $basePath . '/plugins/sqlwizard';

        $storeClass = '\\Beaver\\Plugins\\SqlWizard\\Storage\\ConnectionStore';
        if (!class_exists($storeClass)) {
            throw new \RuntimeException('ConnectionStore do SqlWizard não encontrado');
        }

        $store = new $storeClass($pluginPath);
        $conn  = $store->find($connectionId);

        if ($conn === null) {
            throw new \RuntimeException('Ligação não encontrada: ' . $connectionId);
        }

        $driver = (string) ($conn['driver']   ?? 'mysql');
        $host   = (string) ($conn['host']     ?? '');
        $port   = (string) ($conn['port']     ?? '');
        $db     = $database !== '' ? $database : (string) ($conn['database'] ?? '');
        $user   = (string) ($conn['username'] ?? '');
        $pass   = (string) ($conn['password'] ?? '');

        if ($driver === 'sqlite') {
            $dsn = 'sqlite:' . $db;
        } else {
            $dsn = "{$driver}:host={$host}";
            if ($port !== '') {
                $dsn .= ";port={$port}";
            }
            if ($db   !== '') {
                $dsn .= ";dbname={$db}";
            }
        }

        $pdo = new \PDO($dsn, $user, $pass, [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_TIMEOUT            => 5,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);

        return [$pdo, $driver];
    }

    /**
     * Procura ficheiros de BD diretamente na pasta indicada.
     * Suporta: *.sqlite, *.sqlite3, *.db, *.sql
     * Ignora: .backups/, storage/frankei-reports/, vendor/, node_modules/
     */
    private static function findDatabaseFiles(string $path): array
    {
        $path = rtrim($path, '/');
        if (!is_dir($path)) return [];

        $found    = [];
        $ignorar  = ['.backups', 'vendor', 'node_modules', 'storage/frankei-reports', '.git'];

        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        $iter->setMaxDepth(4);

        foreach ($iter as $file) {
            if (!$file->isFile()) continue;

            $ext = strtolower($file->getExtension());
            if (!in_array($ext, ['sqlite', 'sqlite3', 'db', 'sql'], true)) continue;

            $rel = ltrim(substr($file->getPathname(), strlen($path)), '/');

            // Ignorar pastas indesejadas
            $skip = false;
            foreach ($ignorar as $ig) {
                if (str_starts_with($rel, $ig . '/') || str_contains($rel, '/' . $ig . '/')) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) continue;

            // Ignorar ficheiros de teste/backup
            $base = basename($rel);
            if (preg_match('/\.(bak|old|tmp|swp)/i', $base)) continue;

            $found[] = [
                'key'      => 'file:' . $rel,
                'label'    => $rel,
                'path'     => $file->getPathname(),
                'type'     => $ext,
                'driver'   => 'sqlite',
                'size'     => $file->getSize(),
            ];
        }

        // Ordenar por tamanho (maior primeiro) — mais provável ser a BD principal
        usort($found, fn($a, $b) => $b['size'] <=> $a['size']);

        // Limitar a 10 BDs para não rebentar a UI
        return array_slice($found, 0, 10);
    }

}
