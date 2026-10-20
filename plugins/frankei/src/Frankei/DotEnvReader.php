<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei;

/**
 * Lê o .env de um projeto PHP e extrai configurações de BD.
 */
class DotEnvReader
{
    /**
     * Devolve a lista de bases encontradas no .env do path.
     *
     * Suporta:
     *   DB_DATABASE / DB_CONNECTION                     (framework)
     *   DB_SITE_DATABASE / DB_SITE_CONNECTION           (site)
     *   DB_FORUM_DATABASE / DB_FORUM_CONNECTION         (fórum)
     *
     * @return array<int, array{key:string,label:string,driver:string,path:string}>
     */
    public static function readDatabases(string $projectPath): array
    {
        $envFile = rtrim($projectPath, '/') . '/.env';
        if (!is_file($envFile)) {
            return [];
        }

        $vars = self::parse(file_get_contents($envFile) ?: '');

        $groups = [
            'framework' => ['connection' => 'DB_CONNECTION',   'database' => 'DB_DATABASE'],
            'site'      => ['connection' => 'DB_SITE_CONNECTION',   'database' => 'DB_SITE_DATABASE'],
            'forum'     => ['connection' => 'DB_FORUM_CONNECTION',  'database' => 'DB_FORUM_DATABASE'],
        ];

        $out = [];
        foreach ($groups as $key => $map) {
            $dbPath = $vars[$map['database']] ?? null;
            if (!$dbPath) continue;

            // Resolver caminhos relativos face ao projeto
            if (!preg_match('#^(/|[A-Za-z]:)#', $dbPath)) {
                $dbPath = rtrim($projectPath, '/') . '/' . ltrim($dbPath, '/');
            }

            if (!is_file($dbPath)) continue;

            $out[] = [
                'key'    => $key,
                'label'  => self::label($key),
                'driver' => strtolower($vars[$map['connection']] ?? 'sqlite'),
                'path'   => $dbPath,
            ];
        }

        return $out;
    }

    private static function label(string $key): string
    {
        return match ($key) {
            'framework' => 'Framework (Beaver)',
            'site'      => 'Site oficial',
            'forum'     => 'Fórum',
            default     => ucfirst($key),
        };
    }

    /** @return array<string,string> */
    private static function parse(string $content): array
    {
        $vars = [];
        foreach (preg_split('/\r?\n/', $content) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            if (!str_contains($line, '=')) continue;

            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v);

            // Remover quotes
            if (strlen($v) >= 2) {
                $f = $v[0]; $l = $v[strlen($v) - 1];
                if (($f === '"' && $l === '"') || ($f === "'" && $l === "'")) {
                    $v = substr($v, 1, -1);
                }
            }

            $vars[$k] = $v;
        }
        return $vars;
    }
}
