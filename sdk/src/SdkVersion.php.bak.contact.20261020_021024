<?php

declare(strict_types=1);

/**
 * Beaver Framework — Modern PHP framework with a plugin ecosystem.
 *
 * @package    Beaver SDK
 * @version    0.1.0
 * @author     Franco <onidesk@outlook.com>
 * @copyright  2026 Onidesk
 * @license    MIT <https://opensource.org/licenses/MIT>
 * @link       https://github.com/Onidesk-TI/beaver-framework
 */

namespace Beaver\Sdk;

final class SdkVersion
{
    public const API_VERSION   = '1.0.0';
    public const MIN_FRAMEWORK = '0.1.0';

    public static function satisfies(string $version, string $constraint): bool
    {
        $constraint = trim($constraint);

        if (preg_match('/^>=\s*(\d+\.\d+\.\d+)$/', $constraint, $m)) {
            return version_compare($version, $m[1], '>=');
        }

        if (preg_match('/^\^(\d+\.\d+\.\d+)$/', $constraint, $m)) {
            [$maj] = explode('.', $m[1]);
            return version_compare($version, $m[1], '>=')
                && version_compare($version, ((int) $maj + 1) . '.0.0', '<');
        }

        return false;
    }
}
