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
    | Storage
    |--------------------------------------------------------------------------
    | Opções: 'cache', 'array'
    |
    | - cache → usa o subsistema Cache (default)
    | - array → memória (testes)
    */
    'driver' => getenv('RATE_LIMITER_DRIVER') ?: 'cache',

    /*
    |--------------------------------------------------------------------------
    | Prefixo das chaves no storage
    |--------------------------------------------------------------------------
    */
    'prefix' => 'rate_limiter:',

    /*
    |--------------------------------------------------------------------------
    | Limites default
    |--------------------------------------------------------------------------
    | Usados se o controller não especificar.
    */
    'max_attempts'  => (int) (getenv('RATE_LIMITER_MAX')   ?: 60),
    'decay_seconds' => (int) (getenv('RATE_LIMITER_DECAY') ?: 60),
];
