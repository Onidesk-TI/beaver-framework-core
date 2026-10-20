<?php

declare(strict_types=1);

namespace Beaver\Console\Commands;

use Beaver\Console\Command;
use Beaver\Database\Database;

class QueueRetryCommand extends Command
{
    protected string $signature = 'queue:retry {id?} {--all}';
    protected string $description = 'Re-tenta jobs falhados';

    public function handle(): int
    {
        $db = Database::getInstance();

        $id  = $this->argument('id');
        $all = (bool) $this->option('all');

        if ($id === null && !$all) {
            $this->error('Especifica um ID ou usa --all');
            return self::INVALID;
        }

        $now = time();

        if ($all) {
            $count = $db->update(
                "UPDATE jobs
                 SET failed_at = NULL, last_error = NULL, attempts = 0,
                     reserved_at = NULL, available_at = :now
                 WHERE failed_at IS NOT NULL",
                [':now' => $now],
            );

            $this->success("{$count} job(s) re-enfileirado(s).");
            return self::SUCCESS;
        }

        $count = $db->update(
            "UPDATE jobs
             SET failed_at = NULL, last_error = NULL, attempts = 0,
                 reserved_at = NULL, available_at = :now
             WHERE id = :id AND failed_at IS NOT NULL",
            [':now' => $now, ':id' => $id],
        );

        if ($count === 0) {
            $this->warning("Job {$id} não encontrado ou não está falhado.");
            return self::FAILURE;
        }

        $this->success("Job {$id} re-enfileirado.");
        return self::SUCCESS;
    }
}
