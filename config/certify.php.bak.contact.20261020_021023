<?php

/**
 * Beaver Framework — Modern PHP framework with a plugin ecosystem.
 *
 * @package    Beaver Framework
 * @version    0.1.0
 * @author     Franco <onidesk@outlook.com>
 * @copyright  2026 Onidesk
 * @license    GPL-3.0-or-later <https://www.gnu.org/licenses/gpl-3.0.txt>
 * @link       https://github.com/Onidesk-TI/beaver-framework
 */
// beaver-framework/config/certify.php

return [
    'default' => 'self-signed',

    'modes' => [
        'self-signed' => [
            'driver'   => 'self-signed',
            'days'     => 365,
            'key_size' => 2048,
            'country'  => 'PT',
            'org'      => 'Beaver Local',
        ],
    ],

    'paths' => [
        'storage' => dirname(__DIR__) . '/storage/certificates',
    ],

    'tls' => [
        'min_version'  => 'TLSv1.2',
        'hsts'         => true,
        'hsts_max_age' => 31536000,
    ],
];
