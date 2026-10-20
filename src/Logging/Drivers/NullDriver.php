<?php

declare(strict_types=1);

namespace Beaver\Logging\Drivers;

use Beaver\Logging\Contracts\LogDriver;

class NullDriver implements LogDriver
{
    public function log(string $level, string $message, array $context = []): void
    {
        // no-op
    }
}
