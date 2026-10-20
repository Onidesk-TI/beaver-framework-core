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
    'driver' => getenv('HASH_DRIVER') ?: 'bcrypt',

    'bcrypt' => [
        'rounds' => (int) (getenv('HASH_BCRYPT_ROUNDS') ?: 12),
    ],

    'argon2' => [
        'memory'  => (int) (getenv('HASH_ARGON2_MEMORY')  ?: 65536),
        'time'    => (int) (getenv('HASH_ARGON2_TIME')    ?: 4),
        'threads' => (int) (getenv('HASH_ARGON2_THREADS') ?: 1),
    ],
];
