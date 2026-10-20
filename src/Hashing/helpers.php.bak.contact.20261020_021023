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

use Beaver\Foundation\Application;
use Beaver\Hashing\Hasher;

if (!function_exists('hash_make')) {
    function hash_make(string $value): string
    {
        return Application::getInstance()->make(Hasher::class)->make($value);
    }
}

if (!function_exists('hash_check')) {
    function hash_check(string $value, string $hash): bool
    {
        return Application::getInstance()->make(Hasher::class)->check($value, $hash);
    }
}

if (!function_exists('hash_needs_rehash')) {
    function hash_needs_rehash(string $hash): bool
    {
        return Application::getInstance()->make(Hasher::class)->needsRehash($hash);
    }
}
