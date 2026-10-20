<?php

declare(strict_types=1);

namespace Beaver\Console\Commands;

use Beaver\Console\Command;

class TestPruneCommand extends Command
{
    protected string $signature = 'test:prune {--restore} {--list}';
    protected string $description = 'Move testes com erro de sintaxe para tests/_broken/ (ou restaura)';

    public function handle(): int
    {
        $testsDir  = $this->projectPath . '/tests';
        $brokenDir = $testsDir . '/_broken';

        if (!is_dir($testsDir)) {
            $this->error('Pasta tests/ não existe.');
            return self::FAILURE;
        }

        if ($this->option('restore')) {
            return $this->restore($brokenDir, $testsDir);
        }

        return $this->prune($testsDir, $brokenDir);
    }

    private function prune(string $testsDir, string $brokenDir): int
    {
        $files = $this->listPhpFiles($testsDir, $brokenDir);

        if (!$files) {
            $this->info('Sem ficheiros de teste para verificar.');
            return self::SUCCESS;
        }

        $broken = [];

        foreach ($files as $file) {
            $out  = [];
            $code = 0;
            @exec('php -l ' . escapeshellarg($file) . ' 2>&1', $out, $code);

            if ($code === 0) continue;

            $broken[] = ['file' => $file, 'err' => trim(implode("\n", $out))];
        }

        if (!$broken) {
            $this->success('Todos os testes estão válidos.');
            return self::SUCCESS;
        }

        $this->line();
        $this->warning(count($broken) . ' ficheiro(s) com erro de sintaxe:');
        foreach ($broken as $b) {
            $this->line('  ✗ ' . $this->relative($b['file'], $this->projectPath));
        }
        $this->line();

        if ($this->option('list')) {
            foreach ($broken as $b) {
                $this->line($b['err']);
                $this->line();
            }
            return self::SUCCESS;
        }

        if (!is_dir($brokenDir)) @mkdir($brokenDir, 0755, true);

        $moved = 0;
        foreach ($broken as $b) {
            $rel  = ltrim(substr($b['file'], strlen($this->projectPath)), '/');
            $dest = $brokenDir . '/' . str_replace('/', '__', $rel);
            if (@rename($b['file'], $dest)) $moved++;
        }

        $this->success("Movidos $moved ficheiro(s) para tests/_broken/");
        $this->line('Restaurar:  php beaver test:prune --restore');
        return self::SUCCESS;
    }

    private function restore(string $brokenDir, string $testsDir): int
    {
        if (!is_dir($brokenDir)) {
            $this->info('Nada para restaurar.');
            return self::SUCCESS;
        }

        $files = glob($brokenDir . '/*.php') ?: [];
        if (!$files) {
            $this->info('Pasta _broken/ está vazia.');
            return self::SUCCESS;
        }

        $n = 0;
        foreach ($files as $f) {
            $rel  = basename($f);
            $rel  = str_replace('__', '/', $rel);
            $dest = $testsDir . '/' . $rel;
            if (!is_dir(dirname($dest))) @mkdir(dirname($dest), 0755, true);
            if (@rename($f, $dest)) $n++;
        }

        $this->success("Restaurados $n ficheiro(s) para tests/");
        return self::SUCCESS;
    }

    private function listPhpFiles(string $dir, string $exclude): array
    {
        $out  = [];
        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iter as $f) {
            if (!$f->isFile()) continue;
            if (!str_ends_with($f->getFilename(), '.php')) continue;
            if (str_starts_with($f->getPathname(), $exclude)) continue;
            $out[] = $f->getPathname();
        }
        return $out;
    }

    private function relative(string $abs, string $base): string
    {
        $base = rtrim($base, '/') . '/';
        return str_starts_with($abs, $base) ? substr($abs, strlen($base)) : $abs;
    }
}
