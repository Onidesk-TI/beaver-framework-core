<?php

declare(strict_types=1);

namespace Beaver\Console\Commands;

use Beaver\Console\Command;

class ThemeListCommand extends Command
{
    protected string $signature = 'theme:list {--json}';
    protected string $description = 'Lista os temas instalados em themes/ e o tema ativo';

    public function handle(): int
    {
        $themesDir = $this->projectPath . '/themes';
        $configFile = $this->projectPath . '/storage/themes.json';

        $active = null;
        if (is_file($configFile)) {
            $cfg = json_decode((string) file_get_contents($configFile), true);
            $active = $cfg['active'] ?? null;
        }

        $temas = [];
        if (is_dir($themesDir)) {
            foreach (scandir($themesDir) ?: [] as $item) {
                if ($item === '.' || $item === '..') continue;
                $path = $themesDir . '/' . $item;
                if (!is_dir($path)) continue;

                $temas[] = [
                    'slug'    => $item,
                    'nome'    => $this->lerNome($path) ?: $item,
                    'ativo'   => ($item === $active),
                ];
            }
        }
        usort($temas, fn ($a, $b) => strcmp($a['slug'], $b['slug']));

        if ($this->option('json')) {
            echo json_encode([
                'ativo'  => $active,
                'temas'  => $temas,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
            return self::SUCCESS;
        }

        $this->line();
        $this->info('🎨 Temas instalados');

        if ($active) {
            $this->line("  Ativo: {$active}");
        } else {
            $this->line("  Ativo: (nenhum)");
        }
        $this->line();

        if (!$temas) {
            $this->warning('Nenhum tema encontrado em themes/');
            return self::SUCCESS;
        }

        printf("  %-24s %-30s %s\n", 'SLUG', 'NOME', 'ESTADO');
        printf("  %s\n", str_repeat('─', 70));

        foreach ($temas as $t) {
            printf("  %-24s %-30s %s\n",
                $t['slug'],
                $t['nome'],
                $t['ativo'] ? "\033[32m● ativo\033[0m" : '  —'
            );
        }
        $this->line();
        $this->success(count($temas) . ' tema(s) encontrado(s)');

        return self::SUCCESS;
    }

    private function lerNome(string $dir): ?string
    {
        foreach (['theme.json', 'manifest.json', 'theme.schema.json'] as $f) {
            $file = $dir . '/' . $f;
            if (!is_file($file)) continue;
            $j = json_decode((string) file_get_contents($file), true);
            if (is_array($j) && !empty($j['name'])) return (string) $j['name'];
        }
        return null;
    }
}
