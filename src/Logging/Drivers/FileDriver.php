<?php

declare(strict_types=1);

namespace Beaver\Logging\Drivers;

use Beaver\Logging\Contracts\LogDriver;

/**
 * Driver de log por ficheiro. Um ficheiro por dia:
 *   storage/logs/beaver-YYYY-MM-DD.log
 */
class FileDriver implements LogDriver
{
    public function __construct(
        private string $path,
        private string $prefix = 'beaver',
        private string $dateFormat = 'Y-m-d H:i:s',
    ) {
        if (!is_dir($this->path)) {
            @mkdir($this->path, 0755, true);
        }
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $file = $this->fileForToday();

        $line = sprintf(
            '[%s] %s: %s%s%s',
            date($this->dateFormat),
            $level,
            $message,
            $context !== [] ? ' ' : '',
            $context !== [] ? json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '',
        );

        @file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function fileForToday(): string
    {
        return $this->path . '/' . $this->prefix . '-' . date('Y-m-d') . '.log';
    }
}
