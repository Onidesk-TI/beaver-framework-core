<?php

declare(strict_types=1);

namespace Beaver\Console\Commands;

use Beaver\Console\Command;
use Beaver\Foundation\Application;

class DbSeedCommand extends Command
{
    protected string $signature = 'db:seed {--class=} {--force}';
    protected string $description = 'Corre os seeders em database/seeders/';

    public function handle(): int
    {
        $app = Application::getInstance();
        $app->boot();

        $dir = $this->projectPath . '/database/seeders';

        if (!is_dir($dir)) {
            if (!$this->option('force')) {
                $this->warning("Pasta database/seeders/ não existe.");
                $this->line("  Cria-a e adiciona ficheiros <Nome>Seeder.php com o método run().");
                return self::SUCCESS;
            }
            @mkdir($dir, 0755, true);
            $this->info("Pasta database/seeders/ criada.");
        }

        $alvo = $this->option('class');

        if ($alvo) {
            // Correr só uma classe específica
            $file = $dir . '/' . $alvo . '.php';
            if (!is_file($file)) {
                $this->error("Seeder não encontrado: {$file}");
                return self::FAILURE;
            }
            return $this->correr($file);
        }

        // Correr todos
        $files = glob($dir . '/*Seeder.php') ?: [];

        if (!$files) {
            $this->warning("Nenhum seeder em database/seeders/ (ficheiros *Seeder.php).");
            return self::SUCCESS;
        }

        $this->line();
        $this->info('🌱 A correr seeders');
        $this->line();

        $erros = 0;
        foreach ($files as $file) {
            $rc = $this->correr($file, true);
            if ($rc !== self::SUCCESS) $erros++;
        }

        $this->line();
        if ($erros === 0) {
            $this->success('Todos os seeders correram');
            return self::SUCCESS;
        }

        $this->warning("{$erros} seeder(s) falharam");
        return self::FAILURE;
    }

    private function correr(string $file, bool $resumido = false): int
    {
        $nome = basename($file, '.php');
        $classe = $this->descobrirClasse($file, $nome);

        if (!$classe) {
            if (!$resumido) $this->error("Não consegui identificar a classe em {$nome}.php");
            return self::FAILURE;
        }

        // Autoload dinâmico do ficheiro (fora do PSR-4)
        require_once $file;

        if (!class_exists($classe)) {
            if (!$resumido) $this->error("Classe {$classe} não encontrada após require do ficheiro");
            return self::FAILURE;
        }

        try {
            $obj = new $classe();
            if (!method_exists($obj, 'run')) {
                if (!$resumido) $this->error("{$classe} não tem método run()");
                return self::FAILURE;
            }
            $obj->run();

            if ($resumido) {
                $this->line("  ✓ {$nome}");
            } else {
                $this->success("Correu: {$nome}");
            }
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("{$nome}: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function descobrirClasse(string $file, string $fallback): ?string
    {
        $c = (string) file_get_contents($file);

        // Padrão: class NomeDaClasse
        if (preg_match('/\bclass\s+([A-Z][A-Za-z0-9_]*)/', $c, $m)) {
            $short = $m[1];
            // Se houver namespace, juntar
            if (preg_match('/\bnamespace\s+([A-Z][A-Za-z0-9_\\\\]*)\s*;/', $c, $ns)) {
                return $ns[1] . '\\' . $short;
            }
            return $short;
        }
        return null;
    }
}
