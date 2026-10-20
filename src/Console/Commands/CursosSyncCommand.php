<?php

declare(strict_types=1);

namespace Beaver\Console\Commands;

use Beaver\Console\Command;
use Beaver\Foundation\Application;
use Beaver\Plugins\Cursos\CourseLoader;

class CursosSyncCommand extends Command
{
    protected string $signature = 'cursos:sync {--dir=} {--apenas=} {--quiet}';
    protected string $description = 'Sincroniza os packs de curso (curso.json + questoes.json) com a base de dados';

    public function handle(): int
    {
        $app = Application::getInstance();
        $app->boot();

        $dirs = [];
        if ($opt = $this->option('dir')) {
            $dirs[] = (string) $opt;
        } else {
            $dirs[] = $this->projectPath . '/plugins';
        }

        $quiet = (bool) $this->option('quiet');

        if (!$quiet) {
            $this->line();
            $this->info('🦫  Beaver Cursos — Sync');
            foreach ($dirs as $d) {
                $this->line("  Procurando packs em: {$d}");
            }
            $this->line();
        }

        if (!class_exists(CourseLoader::class)) {
            $this->error('CourseLoader não encontrado. O plugin beaver-cursos está instalado e ativo?');
            return self::FAILURE;
        }

        $loader = new CourseLoader($dirs);
        $report = $loader->syncAll();

        if (!$quiet) {
            foreach ($report['erros'] as $err) {
                $this->error($err);
            }

            if ($report['ok'] > 0) {
                $this->success("Sincronizados: {$report['ok']} curso(s)");
            } else {
                $this->warning('Nenhum curso sincronizado.');
            }

            if (!empty($report['erros'])) {
                $this->warning('Erros: ' . count($report['erros']));
            }

            $this->line();
        }

        return empty($report['erros']) ? self::SUCCESS : self::FAILURE;
    }
}
