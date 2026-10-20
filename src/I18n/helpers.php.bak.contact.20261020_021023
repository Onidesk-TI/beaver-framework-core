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

// beaver-framework/src/I18n/helpers.php
//
// Global translation helpers. Loaded by Foundation\Application::bootI18n()
// after the Translator has been registered in the container.
//
// Requires that Foundation\Application has already been instantiated
// (which is what registers the Translator in the container).

if (!function_exists('__')) {
    /**
     * Translate the given key.
     *
     * @param string               $key     Translation key (e.g. 'welcome')
     * @param array<string, mixed> $replace Placeholder replacements (e.g. ['name' => 'José'])
     */
    function __(string $key, array $replace = []): string
    {
        return \Beaver\Foundation\Application::getInstance()
            ->make(\Beaver\I18n\Translator::class)
            ->get($key, $replace);
    }
}

if (!function_exists('_e')) {
    /**
     * Translate the given key and echo it.
     *
     * @param string               $key     Translation key
     * @param array<string, mixed> $replace Placeholder replacements
     */
    function _e(string $key, array $replace = []): void
    {
        echo __($key, $replace);
    }
}

if (!function_exists('_n')) {
    /**
     * Translate a pluralized key based on a count.
     *
     * Expects the translation to use the "singular|plural" format,
     * e.g. 'items.count' => 'Tens :count item|Tens :count itens'.
     *
     * @param string               $key     Translation key
     * @param int                  $count   Count used to pick the plural form
     * @param array<string, mixed> $replace Placeholder replacements
     */
    function _n(string $key, int $count, array $replace = []): string
    {
        return \Beaver\Foundation\Application::getInstance()
            ->make(\Beaver\I18n\Translator::class)
            ->choice($key, $count, $replace);
    }
}

if (!function_exists('_x')) {
    /**
     * Translate a key for a specific locale, ignoring the active one.
     *
     * @param string               $key     Translation key
     * @param string               $locale  Target locale (e.g. 'en')
     * @param array<string, mixed> $replace Placeholder replacements
     */
    function _x(string $key, string $locale, array $replace = []): string
    {
        return \Beaver\Foundation\Application::getInstance()
            ->make(\Beaver\I18n\Translator::class)
            ->get($key, $replace, $locale);
    }
}
