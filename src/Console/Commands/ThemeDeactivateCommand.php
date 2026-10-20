<?php

declare(strict_types=1);

namespace Beaver\Console\Commands;

use Beaver\Console\Command;

class ThemeDeactivateCommand extends Command
{
    protected string $signature = 'theme:deactivate {--default=beaver-dark}';
    protected string $description = 'Repõe o tema por omissão (storage/themes.json)';

    public function handle(): int
    {
        $default = (string) ($this->option('default') ?: 'beaver-dark');
        $configFile = $this->projectPath . '/storage/themes.json';

        $cfg = is_file($configFile)
            ? (json_decode((string) file_get_contents($configFile), true) ?: [])
            : ['version' => 1];

        $anterior = $cfg['active'] ?? null;
        $cfg['active'] = $default;

        if (!is_dir(dirname($configFile))) {
            @mkdir(dirname($configFile), 0755, true);
        }

        file_put_contents(
            $configFile,
            json_encode($cfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        if ($anterior === $default) {
            $this->info("Tema já era o por omissão: {$default}");
        } else {
            $this->success("Tema reposto: {$default}");
            if ($anterior) {
                $this->line("  (era: {$anterior})");
            }
        }

        return self::SUCCESS;
    }
}
