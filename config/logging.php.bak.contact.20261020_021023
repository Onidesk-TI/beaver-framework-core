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
    | Driver de logging
    |--------------------------------------------------------------------------
    | Opções: 'file', 'null', 'stack'
    |
    | - file  → ficheiro diário em storage/logs/ (default)
    | - null  → não loga
    | - stack → múltiplos destinos
    */
    'driver' => getenv('LOG_DRIVER') ?: 'file',

    /*
    |--------------------------------------------------------------------------
    | Log em ficheiro
    |--------------------------------------------------------------------------
    */
    'file' => [
        'path'   => dirname(__DIR__) . '/storage/logs',
        'prefix' => 'beaver',
    ],

    /*
    |--------------------------------------------------------------------------
    | Stack (múltiplos destinos)
    |--------------------------------------------------------------------------
    | Exemplo:
    |   'driver' => 'stack',
    |   'stack'  => [
    |       'channels' => [
    |           ['driver' => 'file'],
    |           // ['driver' => 'syslog'],
    |       ],
    |   ],
    */
    'stack' => [
        'channels' => [],
    ],
];
