<?php

declare(strict_types=1);

namespace Beaver\Plugins\Frankei;

use Beaver\Plugin\PluginBase;
use Beaver\Foundation\Application;
use Beaver\Plugins\Frankei\Frankei\FrankeiRunner;
use Beaver\Plugins\Frankei\Frankei\ScanContext;
use Beaver\Plugins\Frankei\Frankei\ScannerRegistry;

class FrankeiPlugin extends PluginBase
{
    public function boot(): void
    {
        // Auto-fix de bugs conhecidos (corre 1× por versão)
        try {
            (new \Beaver\Plugins\Frankei\Support\AutoFixService())->run();
        } catch (\Throwable $e) {
            // Nunca deixar o auto-fix rebentar o plugin
            error_log('[frankei autofix] ' . $e->getMessage());
        }

        $routesFile = $this->path . '/routes/web.php';
        if (is_file($routesFile)) {
            $this->loadRoutes();
        }
        $this->loadViews();
        $this->loadTranslations();
        $this->registerConsoleCommands();
    }

    private function registerConsoleCommands(): void
    {
        if (!class_exists(\Beaver\Plugins\Console\Registry::class)) {
            return;
        }

        \Beaver\Plugins\Console\Registry::register(
            'beaver:frankei:info',
            function (array $args) {
                $registry = new ScannerRegistry();
                return [
                    'ok'      => true,
                    'message' => count($registry->all()) . ' scanner(s)',
                    'data'    => ['scanners' => $registry->metadata()],
                ];
            },
            [
                'description' => 'Lista os scanners de segurança disponíveis',
                'usage'       => 'beaver:frankei:info',
                'plugin'      => 'frankei',
            ]
        );

        \Beaver\Plugins\Console\Registry::register(
            'beaver:frankei:analyse',
            fn(array $args) => $this->analyse($args),
            [
                'description' => 'Corre scanners de segurança numa ligação/database',
                'usage'       => 'beaver:frankei:analyse --connection=<id> [--database=<db>] [--scanners=s1,s2]',
                'plugin'      => 'frankei',
            ]
        );
    }

    private function analyse(array $args): array
    {
        $connectionId = (string) ($args['connection'] ?? '');
        $database     = (string) ($args['database']   ?? '');
        $scannersRaw  = (string) ($args['scanners']   ?? '');

        if ($connectionId === '') {
            return $this->listConnections();
        }

        $scannerIds = $scannersRaw === ''
        ? []
        : array_values(array_filter(array_map('trim', explode(',', $scannersRaw))));


        $scannerIds = $scannersRaw === ''
            ? []
            : array_values(array_filter(array_map('trim', explode(',', $scannersRaw))));

        try {
            [$pdo, $driver] = $this->openConnection($connectionId, $database);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        $ctx = new ScanContext(
            pdo:      $pdo,
            driver:   $driver,
            database: $database,
            tables:   [],
            mode:     'readonly',
        );

        try {
            $registry = new ScannerRegistry();
            $runner   = new FrankeiRunner($registry);
            $result   = $runner->run($ctx, $scannerIds);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        $stats = $result['stats'] ?? [];

        return [
            'ok'      => true,
            'message' => ($stats['total'] ?? 0) . ' finding(s)',
            'data'    => [
                'stats'    => $stats,
                'findings' => $result['findings'] ?? [],
                'errors'   => $result['errors']   ?? [],
            ],
        ];
    }

    private function listConnections(): array
    {
        $basePath   = Application::getInstance()->basePath;
        $pluginPath = $basePath . '/plugins/sqlwizard';

        $storeClass = '\\Beaver\\Plugins\\SqlWizard\\Storage\\ConnectionStore';
        if (!class_exists($storeClass)) {
            return ['ok' => false, 'message' => 'ConnectionStore do SqlWizard não encontrado'];
        }

        $store = new $storeClass($pluginPath);

    // tenta all() → list() → toArray() para não depender do nome exato
        $all = null;
        foreach (['all', 'list', 'toArray'] as $m) {
            if (method_exists($store, $m)) {
                $all = $store->$m();
                break;
            }
        }

        if (!is_array($all)) {
            return ['ok' => false, 'message' => 'ConnectionStore não expõe listagem'];
        }

        $rows = [];
        foreach ($all as $c) {
            if (!is_array($c)) {
                continue;
            }
            $rows[] = [
            'id'       => $c['id']       ?? '',
            'name'     => $c['name']     ?? '',
            'driver'   => $c['driver']   ?? '',
            'host'     => $c['host']     ?? '',
            'database' => $c['database'] ?? '',
            ];
        }

        return [
        'ok'      => true,
        'message' => count($rows) . ' ligação(ões) — usa --connection=<id>',
        'data'    => ['connections' => $rows],
        ];
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
}
