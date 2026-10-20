<?php

declare(strict_types=1);

namespace Beaver\Logging;

final class LogLevel
{
    public const DEBUG    = 'DEBUG';
    public const INFO     = 'INFO';
    public const WARNING  = 'WARNING';
    public const ERROR    = 'ERROR';
    public const CRITICAL = 'CRITICAL';

    /** @return array<int, string> Ordem de severidade (mais baixo = mais grave) */
    public static function all(): array
    {
        return [
            self::DEBUG,
            self::INFO,
            self::WARNING,
            self::ERROR,
            self::CRITICAL,
        ];
    }
}
