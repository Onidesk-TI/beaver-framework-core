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

// beaver-framework/src/Console/Command.php

namespace Beaver\Console;

abstract class Command
{
    public const SUCCESS = 0;
    public const FAILURE = 1;
    public const INVALID = 2;

    protected string $signature = '';
    protected string $description = '';

    protected array $args = [];
    protected array $options = [];

    protected string $projectPath = '';

    abstract public function handle(): int;

    public function signature(): string
    {
        return $this->signature;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function setProjectPath(string $path): void
    {
        $this->projectPath = $path;
    }

    public function name(): string
    {
        $parts = preg_split('/\s+/', trim($this->signature));
        return $parts[0] ?? '';
    }

    public function parse(array $argv): void
    {
        $this->args = [];
        $this->options = [];

        preg_match_all('/\{([^}]+)\}/', $this->signature, $matches);
        $argDefs = $matches[1] ?? [];

        $positionalNames = [];
        foreach ($argDefs as $def) {
            if (str_starts_with($def, '--')) {
                $opt = explode('=', ltrim($def, '-'), 2);
                $optName = trim(explode(':', $opt[0])[0]);
                $default = $opt[1] ?? null;
                $default = $default !== null ? trim(explode(':', $default)[0]) : null;
                $this->options[$optName] = $default;
            } else {
                $name = trim(explode(':', $def)[0]);
                $name = rtrim($name, '?');
                $positionalNames[] = $name;
            }
        }

        $posIndex = 0;
        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--')) {
                $parts = explode('=', substr($arg, 2), 2);
                $this->options[$parts[0]] = $parts[1] ?? true;
            } else {
                if (isset($positionalNames[$posIndex])) {
                    $this->args[$positionalNames[$posIndex]] = $arg;
                } else {
                    $this->args[] = $arg;
                }
                $posIndex++;
            }
        }
    }

    public function argument(string $name, mixed $default = null): mixed
    {
        return $this->args[$name] ?? $default;
    }

    public function option(string $name, mixed $default = null): mixed
    {
        return $this->options[$name] ?? $default;
    }

    protected function lang(): string
    {
        $lang = $this->option('lang');

        if ($lang === null) {
            $env = getenv('BEAVER_LANG');
            $lang = $env !== false ? $env : null;
        }

        if ($lang === null && function_exists('env')) {
            $lang = env('BEAVER_LANG');
        }

        if ($lang === null && function_exists('env')) {
            $lang = env('APP_LOCALE');
        }

        $lang = $lang ?? 'en';

        return substr((string) $lang, 0, 2);
    }

    protected function confirm(string $question, bool $default = false): bool
    {
        if (!function_exists('posix_isatty') || !@posix_isatty(STDIN)) {
            return $default;
        }

        $hint = $default ? '[S/n]' : '[s/N]';
        echo "{$question} {$hint}: ";

        $answer = trim((string) fgets(STDIN));

        if ($answer === '') {
            return $default;
        }

        $answer = strtolower($answer);

        if (in_array($answer, ['s', 'sim', 'y', 'yes'], true)) {
            return true;
        }

        if (in_array($answer, ['n', 'nao', 'não', 'no'], true)) {
            return false;
        }

        $this->warning('Resposta inválida. Usa "s" ou "n".');
        return $this->confirm($question, $default);
    }

    protected function ask(string $question, string $default = ''): string
    {
        if (!function_exists('posix_isatty') || !@posix_isatty(STDIN)) {
            return $default;
        }

        $hint = $default !== '' ? " [{$default}]" : '';
        echo "{$question}{$hint}: ";

        $answer = trim((string) fgets(STDIN));

        if ($answer === '') {
            return $default;
        }

        return $answer;
    }

    protected function info(string $msg): void
    {
        echo "\033[36m › {$msg}\033[0m\n";
    }

    protected function success(string $msg): void
    {
        echo "\033[32m ✓ {$msg}\033[0m\n";
    }

    protected function warning(string $msg): void
    {
        echo "\033[33m ! {$msg}\033[0m\n";
    }

    protected function error(string $msg): void
    {
        echo "\033[31m ✗ {$msg}\033[0m\n";
    }

    protected function line(string $msg = ''): void
    {
        echo "{$msg}\n";
    }
}
