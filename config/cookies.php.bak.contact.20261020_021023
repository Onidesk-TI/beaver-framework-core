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
    | Opções default dos cookies
    |--------------------------------------------------------------------------
    |
    | path     → caminho onde o cookie é válido
    | domain   → domínio (null = domínio atual)
    | secure   → true = só HTTPS
    | httponly → true = JavaScript não pode ler (protege contra XSS)
    | samesite → 'Lax' | 'Strict' | 'None' (protege contra CSRF)
    | ttl      → tempo de vida em segundos (0 = até fechar o browser)
    */
    'path'     => '/',
    'domain'   => null,
    'secure'   => filter_var(getenv('COOKIE_SECURE') ?: 'false', FILTER_VALIDATE_BOOL),
    'httponly' => true,
    'samesite' => getenv('COOKIE_SAMESITE') ?: 'Lax',
    'ttl'      => 0,
];
