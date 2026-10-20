<?php

declare(strict_types=1);

namespace Beaver\Plugins\Translations;

use Beaver\Http\Request;
use Beaver\Http\Response;

class TranslationController
{
    /**
     * GET /lang/{locale}
     * O Router chama: $handler($request, ...$params)
     * → setLocale($request, 'en')
     */
    public function setLocale(Request $request, string $locale = ''): Response
    {
        $locale = strtolower($locale);

        if (!in_array($locale, TranslationsPlugin::SUPPORTED, true)) {
            $locale = TranslationsPlugin::DEFAULT;
        }

        $ref  = $request->headers['REFERER'] ?? ($_SERVER['HTTP_REFERER'] ?? '/');
        $path = parse_url($ref, PHP_URL_PATH) ?: '/';
        $qs   = parse_url($ref, PHP_URL_QUERY);
        parse_str($qs ?? '', $q);
        $q['lang'] = $locale;
        $target    = $path . '?' . http_build_query($q);

        return Response::redirect($target, 302);
    }

    /**
     * GET/POST /lang → JSON
     */
    public function setLocaleJson(Request $request): Response
    {
        $locale = $request->body['locale']
            ?? $request->query['locale']
            ?? $_POST['locale']
            ?? $_GET['locale']
            ?? TranslationsPlugin::DEFAULT;

        $locale = strtolower((string) $locale);

        return Response::json([
            'ok'     => in_array($locale, TranslationsPlugin::SUPPORTED, true),
            'locale' => $locale,
            'labels' => TranslationsPlugin::labels(),
        ]);
    }
}
