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

namespace Beaver\Database\Model\Casts;

/**
 * Cast de datas — usa Carbon (se instalado).
 *
 * Vantagens:
 *  - diffForHumans() nativo
 *  - translatedFormat() com i18n
 *  - API familiar para quem vem do Laravel
 *
 * Requer: composer require nesbot/carbon
 */
class CarbonDate implements CastInterface
{
    public static function apply(mixed $value): mixed
    {
        if (!class_exists(\Carbon\CarbonImmutable::class)) {
            throw new \RuntimeException(
                'Carbon não está instalado. Corre: composer require nesbot/carbon'
            );
        }

        if ($value instanceof \DateTimeInterface) {
            return \Carbon\CarbonImmutable::instance($value);
        }

        if ($value === null || $value === '') {
            return null;
        }

        try {
            return \Carbon\CarbonImmutable::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
