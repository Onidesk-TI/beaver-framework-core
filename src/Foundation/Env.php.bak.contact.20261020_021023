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

namespace Beaver\Foundation;

/**
 * Minimal .env loader.
 *
 * Parses KEY=VALUE lines into getenv() and $_ENV.
 * Supports comments (#), blank lines, optional quotes,
 * and ${VAR} interpolation.
 */
class Env
{
    public static function load(string $file): void
    {
        if (!is_file($file)) {
            return;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);

            // strip matching surrounding quotes
            if (
                strlen($value) >= 2
                && ($value[0] === '"' || $value[0] === "'")
                && $value[0] === $value[-1]
            ) {
                $value = substr($value, 1, -1);
            }

            // ${VAR} interpolation
            $value = preg_replace_callback('/\$\{([A-Z0-9_]+)\}/', function ($m) {
                return getenv($m[1]) ?: '';
            }, $value);

            // do not override real environment variables
            if (getenv($key) === false) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}
