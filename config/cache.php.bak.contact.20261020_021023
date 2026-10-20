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

return [
    /*
    |--------------------------------------------------------------------------
    | Driver de cache
    |--------------------------------------------------------------------------
    | Opções: 'file', 'array', 'null'
    |
    | - file  → ficheiros em storage/cache/ (default)
    | - array → em memória (testes, não persiste)
    | - null  → não cacheia (produção sem cache)
    */
    'driver' => getenv('CACHE_DRIVER') ?: 'file',

    /*
    |--------------------------------------------------------------------------
    | Cache por ficheiro
    |--------------------------------------------------------------------------
    */
    'file' => [
        'path'   => dirname(__DIR__) . '/storage/cache',
        'prefix' => 'beaver_cache_',
    ],

    /*
    |--------------------------------------------------------------------------
    | TTL por omissão (segundos)
    |--------------------------------------------------------------------------
    */
    'ttl' => (int) (getenv('CACHE_TTL') ?: 3600),
];
