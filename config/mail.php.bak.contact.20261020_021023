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
    | Driver de email
    |--------------------------------------------------------------------------
    | Opções: 'smtp', 'log', 'array'
    |
    | - smtp  → envio real (SMTP)
    | - log   → escreve em storage/logs/mail-YYYY-MM-DD.log
    | - array → testes (memória)
    */
    'driver' => getenv('MAIL_DRIVER') ?: 'log',

    /*
    |--------------------------------------------------------------------------
    | SMTP
    |--------------------------------------------------------------------------
    */
    'host'       => getenv('MAIL_HOST')       ?: 'localhost',
    'port'       => (int) (getenv('MAIL_PORT') ?: 587),
    'username'   => getenv('MAIL_USERNAME')   ?: '',
    'password'   => getenv('MAIL_PASSWORD')   ?: '',
    'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
    'timeout'    => (int) (getenv('MAIL_TIMEOUT') ?: 15),

    /*
    |--------------------------------------------------------------------------
    | Remetente default
    |--------------------------------------------------------------------------
    */
    'from' => [
        'address' => getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@beaver.local',
        'name'    => getenv('MAIL_FROM_NAME')    ?: 'Beaver Framework',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pasta do LogDriver
    |--------------------------------------------------------------------------
    */
    'log_path' => dirname(__DIR__) . '/storage/logs',
];
