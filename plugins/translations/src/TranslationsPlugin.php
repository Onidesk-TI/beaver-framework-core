<?php

declare(strict_types=1);

namespace Beaver\Plugins\Translations;

use Beaver\Plugin\PluginBase;

class TranslationsPlugin extends PluginBase
{
    public const SUPPORTED = ['pt', 'es', 'en', 'fr', 'de', 'it'];
    public const DEFAULT   = 'pt';
    public const COOKIE    = 'beaver-lang';

    public function boot(): void
    {
        $this->loadRoutes();
    }

    public static function labels(): array
    {
        return [
            'pt' => ['label' => 'Português', 'flag' => '🇵🇹'],
            'es' => ['label' => 'Español',   'flag' => '🇪🇸'],
            'en' => ['label' => 'English',   'flag' => '🇬🇧'],
            'fr' => ['label' => 'Français',  'flag' => '🇫🇷'],
            'de' => ['label' => 'Deutsch',   'flag' => '🇩🇪'],
            'it' => ['label' => 'Italiano',  'flag' => '🇮🇹'],
        ];
    }

    public static function currentLocale(): string
    {
        $env = getenv('BEAVER_LANG');
        if ($env && in_array(strtolower($env), self::SUPPORTED, true)) {
            return strtolower($env);
        }
        if (!empty($_COOKIE[self::COOKIE])) {
            $c = strtolower(preg_replace('/[^a-z]/', '', $_COOKIE[self::COOKIE]));
            if (in_array($c, self::SUPPORTED, true)) {
                return $c;
            }
        }
        if (!empty($_GET['lang']) && in_array(strtolower($_GET['lang']), self::SUPPORTED, true)) {
            return strtolower($_GET['lang']);
        }
        return self::DEFAULT;
    }
}
