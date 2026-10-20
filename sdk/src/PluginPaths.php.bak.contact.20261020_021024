<?php

declare(strict_types=1);

/**
 * Beaver Framework — Modern PHP framework with a plugin ecosystem.
 *
 * @package    Beaver SDK
 * @version    0.1.0
 * @author     Franco <onidesk@outlook.com>
 * @copyright  2026 Onidesk
 * @license    MIT <https://opensource.org/licenses/MIT>
 * @link       https://github.com/Onidesk-TI/beaver-framework
 */

namespace Beaver\Sdk;

/**
 * Resolve a lista de paths a varrer, conforme o mode configurado.
 *
 * Fonte de verdade única para o PluginManager (core) e para os
 * comandos CLI (plugin:list, plugin:validate, plugin:info).
 *
 * Ordem importa: o primeiro plugin encontrado com um dado slug ganha.
 */
final class PluginPaths
{
    /**
     * @param  string $mode   'dev' | 'staging' | 'prod'
     * @param  array  $config  ex: ['internal_path' => '...', 'dev_path' => '...', ...]
     * @return array<int,array{path:string,source:string}>
     */
    public static function discover(string $mode, array $config): array
    {
        $pick = static fn (string $key): string => rtrim((string) ($config[$key] ?? ''), '/\\');

        return match ($mode) {
            'dev' => [
                ['path' => $pick('internal_path'), 'source' => 'internal'],
                ['path' => $pick('dev_path'),      'source' => 'dev'],
                ['path' => $pick('staging_path'),  'source' => 'staging'],
                ['path' => $pick('path'),          'source' => 'prod'],
            ],
            'staging' => [
                ['path' => $pick('internal_path'), 'source' => 'internal'],
                ['path' => $pick('staging_path'),  'source' => 'staging'],
                ['path' => $pick('path'),          'source' => 'prod'],
            ],
            default => [
                ['path' => $pick('internal_path'), 'source' => 'internal'],
                ['path' => $pick('path'),          'source' => 'prod'],
            ],
        };
    }

    /**
     * Descobre todos os manifestos encontrados.
     *
     * @return array<int,array{slug:string,path:string,manifest:string,source:string,data:array}>
     */
    public static function manifests(string $mode, array $config): array
    {
        $out  = [];
        $seen = [];

        foreach (self::discover($mode, $config) as $entry) {
            if ($entry['path'] === '' || !is_dir($entry['path'])) {
                continue;
            }

            foreach (glob($entry['path'] . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
                $slug = basename($dir);
                if ($slug === '' || str_starts_with($slug, '.')) {
                    continue;
                }
                if (isset($seen[$slug])) {
                    continue;
                }

                $manifestFile = $dir . '/plugin.json';
                if (!is_file($manifestFile)) {
                    continue;
                }

                $seen[$slug] = true;

                $out[] = [
                    'slug'     => $slug,
                    'path'     => $dir,
                    'manifest' => $manifestFile,
                    'source'   => $entry['source'],
                    'data'     => json_decode((string) file_get_contents($manifestFile), true) ?? [],
                ];
            }
        }

        usort($out, static fn ($a, $b) => strcmp($a['slug'], $b['slug']));
        return $out;
    }
}
