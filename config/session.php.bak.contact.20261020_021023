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
    | Driver de sessão
    |--------------------------------------------------------------------------
    | Opções: 'native', 'array'
    |
    | - native  → $_SESSION (default, PHP nativo)
    | - array   → em memória (testes, não persiste)
    */
    'driver' => getenv('SESSION_DRIVER') ?: 'native',

    /*
    |--------------------------------------------------------------------------
    | Nome do cookie de sessão
    |--------------------------------------------------------------------------
    */
    'cookie' => getenv('SESSION_COOKIE') ?: 'beaver_session',

    /*
    |--------------------------------------------------------------------------
    | Tempo de vida (minutos)
    |--------------------------------------------------------------------------
    */
    'lifetime' => (int) (getenv('SESSION_LIFETIME') ?: 120),

    /*
    |--------------------------------------------------------------------------
    | Segurança
    |--------------------------------------------------------------------------
    */
    'secure'   => filter_var(getenv('SESSION_SECURE') ?: 'false', FILTER_VALIDATE_BOOL),
    'httponly' => true,
    'samesite' => 'Lax',

    /*
    |--------------------------------------------------------------------------
    | Caminho do cookie
    |--------------------------------------------------------------------------
    */
    'path'   => '/',
    'domain' => null,
];
