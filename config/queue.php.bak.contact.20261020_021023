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
    | Driver de fila
    |--------------------------------------------------------------------------
    | Opções: 'sync', 'database', 'array'
    |
    | - sync     → executa o job logo (dev)
    | - database → guarda na tabela `jobs` (produção)
    | - array    → memória (testes)
    */
    'driver' => getenv('QUEUE_DRIVER') ?: 'sync',

    /*
    |--------------------------------------------------------------------------
    | Tabela (driver database)
    |--------------------------------------------------------------------------
    */
    'table' => 'jobs',

    /*
    |--------------------------------------------------------------------------
    | Fila por omissão
    |--------------------------------------------------------------------------
    */
    'default_queue' => 'default',
];
