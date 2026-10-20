<?php

declare(strict_types=1);

namespace Beaver\Console\Commands;

use Beaver\Console\Command;

class ThemeActivateCommand extends Command
{
    protected string $signature = 'theme:activate {slug}';
    protected string $description = 'Ativa um tema (escreve em storage/themes.json)';

    public function handle(): int
    {
        $slug = trim((string) $this->argument('slug'));
        if ($slug === '') {
            $this->error('Falta o slug do tema. Ex: theme:activate beaver-dark');
            return self::INVALID;
        }

        $themesDir = $this->projectPath . '/themes';
        $themePath = $themesDir . '/' . $slug;

        if (!is_dir($themePath)) {
            $this->error("Tema não encontrado: themes/{$slug}");
            $this->line();
            $this->info('Temas disponíveis:');
            $this->listarDisponiveis($themesDir);
            return self::FAILURE;
        }

        $configFile = $this->projectPath . '/storage/themes.json';

        $cfg = is_file($configFile)
            ? (json_decode((string) file_get_contents($configFile), true) ?: [])
            : ['version' => 1];

        $cfg['active'] = $slug;

        if (!is_dir(dirname($configFile))) {
            @mkdir(dirname($configFile), 0755, true);
        }

        file_put_contents(
            $configFile,
            json_encode($cfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        $this->success("Tema ativado: {$slug}");
        return self::SUCCESS;
    }

    private function listarDisponiveis(string $dir): void
    {
        if (!is_dir($dir)) {
            $this->warning('  (pasta themes/ não existe)');
            return;
        }
        $achou = false;
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') continue;
            if (is_dir($dir . '/' . $item)) {
                $this->line("  ▸ {$item}");
                $achou = true;
            }
        }
        if (!$achou) {
            $this->warning('  (nenhum tema instalado)');
        }
    }
}
