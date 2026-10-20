<?php

declare(strict_types=1);

namespace Beaver\Console\Commands;

use Beaver\Console\Command;
use Beaver\Scheduling\JobRunner;
use Beaver\Scheduling\ScheduledJob;
use Throwable;

class JobRunCommand extends Command
{
    protected string $signature   = 'job:run {name} {--force} {--dry-run}';
    protected string $description = 'Executa um job agendado pelo seu name';

    public function handle(): int
    {
        $name   = (string) $this->argument('name');
        $force  = (bool)   $this->option('force');
        $dryRun = (bool)   $this->option('dry-run');

        if ($name === '') {
            $this->error('Falta o nome do job. Ex.: php beaver job:run importar-combustivel');
            return self::INVALID;
        }

        // 1. Procurar o job na BD
        $job = ScheduledJob::where('name', $name)->first();

        if ($job === null) {
            $this->error("Job '{$name}' não encontrado.");
            return self::FAILURE;
        }

        // 2. Verificar se pode correr (is_active E enabled)
        $isActive  = (bool) ($job->is_active ?? false);
        $isEnabled = (bool) ($job->enabled   ?? false);

        if ((!$isActive || !$isEnabled) && !$force) {
            $motivo = !$isActive ? 'inativo' : 'desativado (enabled=0)';
            $this->warning("Job '{$name}' está {$motivo}. Usa --force para correr mesmo assim.");
            return self::FAILURE;
        }

        // 3. Dry-run: só mostra o que faria
        if ($dryRun) {
            $this->info("Job encontrado: {$job->name}");
            $this->line("  handler:         {$job->handler}");
            $this->line("  cron_expression: {$job->cron_expression}");
            $this->line("  is_active:       " . ($isActive ? 'sim' : 'não'));
            $this->line("  enabled:         " . ($isEnabled ? 'sim' : 'não'));
            $this->warning('Dry-run: nada foi executado.');
            return self::SUCCESS;
        }

        // 4. Confirmar (a menos que --force)
        if (!$force && !$this->confirm("Executar o job '{$name}'?", true)) {
            $this->warning('Cancelado.');
            return self::SUCCESS;
        }

        // 5. Correr
        $this->info("A executar '{$name}'...");
        $start = microtime(true);

        try {
            $runner = new JobRunner();
            $runner->run($job);
            $elapsed = round(microtime(true) - $start, 3);

            $this->success("Concluído em {$elapsed}s.");
            return self::SUCCESS;
        } catch (Throwable $e) {
            // O JobRunner já chamou markAsFailed() antes de relançar.
            $this->error("Falhou: {$e->getMessage()}");

            log_error('Job falhou', [
                'name'  => $name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }
}
