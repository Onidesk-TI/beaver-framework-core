<?php

declare(strict_types=1);

namespace Beaver\Console\Commands;

use Beaver\Console\Command;

class AboutCommand extends Command
{
    protected string $signature = 'about';
    protected string $description = 'Sobre o Beaver Framework';

    public function handle(): int
    {
        $version = function_exists('beaver_version') ? beaver_version() : 'dev';

        $this->line();
        $this->line('  🦫 Beaver Framework v' . $version);
        $this->line('  Modern PHP framework with a plugin ecosystem.');
        $this->line();
        $this->line('  GitHub: https://github.com/Onidesk-TI/beaver-framework');
        $this->line('  License: MIT');
        $this->line();

        return self::SUCCESS;
    }
}
