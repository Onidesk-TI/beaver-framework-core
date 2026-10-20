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

namespace Beaver\I18n;

class Locale
{
    public const SUPPORTED = ['pt', 'en'];

    /** Deteta o idioma preferido a partir do header Accept-Language do browser */
    public static function fromBrowser(?string $acceptLanguage = null): string
    {
        $header = $acceptLanguage ?? ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'pt');

        // ex: "pt-PT,pt;q=0.9,en;q=0.8"
        preg_match_all('/([a-z]{2})(?:-[A-Z]{2})?(?:;q=([0-9.]+))?/i', $header, $m, PREG_SET_ORDER);

        $candidates = [];
        foreach ($m as $match) {
            $lang = strtolower($match[1]);
            $q    = isset($match[2]) && $match[2] !== '' ? (float) $match[2] : 1.0;
            $candidates[$lang] = max($candidates[$lang] ?? 0, $q);
        }

        arsort($candidates);

        foreach (array_keys($candidates) as $lang) {
            if (in_array($lang, self::SUPPORTED, true)) {
                return $lang;
            }
        }

        return 'pt';
    }
}
