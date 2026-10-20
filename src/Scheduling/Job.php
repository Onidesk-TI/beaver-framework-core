<?php

declare(strict_types=1);

namespace Beaver\Scheduling;

/**
 * Base opcional para jobs agendados.
 *
 * Não confundir com Beaver\Queue\Job — esse é para jobs
 * assíncronos despachados via dispatch(). Este é para jobs
 * que correm via cron do SO (php beaver job:run <name>).
 *
 * Estender esta classe é opcional: o JobRunner só exige um
 * método handle(). Se estenderes, ganhas acesso ao
 * ScheduledJob, ao payload, e ao helper lastRun().
 */
abstract class Job
{
    protected ?ScheduledJob $scheduledJob = null;

    /** @var array<string, mixed> */
    protected array $payload = [];

    public function setScheduledJob(ScheduledJob $job): void
    {
        $this->scheduledJob = $job;
    }

    /** @param array<string, mixed> $payload */
    public function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }

    /**
     * Timestamp da última execução, ou null se for a primeira vez.
     * Útil para jobs incrementais ("importar só o que é novo").
     */
    protected function lastRun(): ?int
    {
        $at = $this->scheduledJob?->last_run_at;

        if ($at === null || $at === '') {
            return null;
        }

        $ts = strtotime((string) $at);

        return $ts !== false ? $ts : null;
    }

    abstract public function handle(): void;
}
