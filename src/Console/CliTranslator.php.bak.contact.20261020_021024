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

namespace Beaver\Console;

class CliTranslator
{
    private array $lines = [];
    private string $lang;

    public function __construct(string $lang = 'en', string $path = '')
    {
        $this->lang = $lang;
        $file = rtrim($path, '/') . "/{$lang}.php";

        if (is_file($file)) {
            $this->lines = require $file;
        }
    }

    public function get(string $key, array $replace = []): string
    {
        $line = $this->lines[$key] ?? $key;

        foreach ($replace as $k => $v) {
            $line = str_replace(':' . $k, (string) $v, $line);
        }

        return $line;
    }
}
