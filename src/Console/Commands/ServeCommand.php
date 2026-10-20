<?php

declare(strict_types=1);

namespace Beaver\Console\Commands;

use Beaver\Console\Command;

class ServeCommand extends Command
{
    protected string $signature = 'serve {--host=localhost} {--port=9000}';
    protected string $description = 'Arranca o servidor embutido do PHP para desenvolvimento';

    public function handle(): int
    {
        $host = (string) ($this->option('host') ?: 'localhost');
        $port = (int)    ($this->option('port') ?: 9000);
        $root = $this->projectPath . '/public';

        if (!is_dir($root)) {
            $this->error("Pasta public/ não encontrada em {$root}");
            return self::FAILURE;
        }

        $index = $root . '/index.php';
        if (!is_file($index)) {
            $this->error("Ficheiro index.php não encontrado em {$root}");
            return self::FAILURE;
        }

        $this->line();
        $this->info("🦫  Beaver — Servidor de desenvolvimento");
        $this->line("  URL   → http://{$host}:{$port}");
        $this->line("  Raiz  → {$root}");
        $this->line("  Ctrl+C para parar");
        $this->line();

        $cmd = sprintf(
            'php -S %s:%d -t %s %s',
            escapeshellarg($host),
            $port,
            escapeshellarg($root),
            escapeshellarg($index)
        );

        passthru($cmd, $code);

        return $code === 0 ? self::SUCCESS : self::FAILURE;
    }
}
