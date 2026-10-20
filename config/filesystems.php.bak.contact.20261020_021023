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
    | Disco default
    |--------------------------------------------------------------------------
    */
    'default' => getenv('FILESYSTEM_DISK') ?: 'local',

    /*
    |--------------------------------------------------------------------------
    | Discos
    |--------------------------------------------------------------------------
    */
    'disks' => [
        // Ficheiros privados (storage/app/)
        'local' => [
            'driver' => 'local',
            'root'   => dirname(__DIR__) . '/storage/app',
        ],

        // Ficheiros públicos (storage/public/)
        // Acessíveis via /storage/*
        'public' => [
            'driver' => 'local',
            'root'   => dirname(__DIR__) . '/storage/public',
            'url'    => '/storage',
        ],

        // No-op (testes)
        'null' => [
            'driver' => 'null',
        ],
    ],
];
