<?php

declare(strict_types=1);

namespace Beaver\Console\Commands;

use Beaver\Console\Command;

class DbStatusCommand extends Command
{
    protected string $signature = 'db:status';
    protected string $description = 'Alias de migrate:status';

    public function handle(): int
    {
        return (new MigrateStatusCommand())->handle();
    }
}
