<?php

declare(strict_types=1);

namespace Beaver\Console\Commands;

use Beaver\Console\Command;

class CacheClearCommand extends Command
{
    protected string $signature = 'cache:clear {--quiet}';
    protected string $description = 'Limpa a cache (views compiladas, dados temporários)';

    public function handle(): int
    {
        $quiet = (bool) $this->option('quiet');

        $dirs = [
            'storage/cache',
            'storage/cache/views',
            'storage/framework/views',
            'storage/views',
        ];

        $total = 0;
        $erros = [];

        foreach ($dirs as $rel) {
            $dir = $this->projectPath . '/' . $rel;
            if (!is_dir($dir)) continue;

            foreach (glob($dir . '/*') ?: [] as $f) {
                if (is_file($f)) {
                    if (@unlink($f)) {
                        $total++;
                    } else {
                        $erros[] = $f;
                    }
                }
            }
        }

        if (!$quiet) {
            if ($total > 0) {
                $this->success("Cache limpa: {$total} ficheiro(s) removido(s)");
            } else {
                $this->info("Cache já estava vazia");
            }
            foreach ($erros as $e) {
                $this->error("Não consegui apagar: {$e}");
            }
        }

        return empty($erros) ? self::SUCCESS : self::FAILURE;
    }
}
