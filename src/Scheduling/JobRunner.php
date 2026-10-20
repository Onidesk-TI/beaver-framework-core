<?php

declare(strict_types=1);

namespace Beaver\Scheduling;

use RuntimeException;
use Throwable;

/**
 * JobRunner — resolve o handler de um ScheduledJob e executa-o.
 *
 * O handler é uma classe com um método handle(). Opcionalmente
 * pode ter setScheduledJob() e setPayload() para receber
 * contexto do agendamento (ver Beaver\Scheduling\Job).
 */
class JobRunner
{
    public function run(ScheduledJob $scheduled): void
    {
        $handlerClass = (string) $scheduled->handler;

        if ($handlerClass === '') {
            throw new RuntimeException(
                "Job '{$scheduled->name}' não tem handler definido."
            );
        }

        if (!class_exists($handlerClass)) {
            throw new RuntimeException(
                "Handler '{$handlerClass}' não existe (job '{$scheduled->name}')."
            );
        }

        $handler = new $handlerClass();

        if (!method_exists($handler, 'handle')) {
            throw new RuntimeException(
                "Handler '{$handlerClass}' não tem método handle()."
            );
        }

        if (method_exists($handler, 'setScheduledJob')) {
            $handler->setScheduledJob($scheduled);
        }

        if (method_exists($handler, 'setPayload')) {
            $handler->setPayload($scheduled->payloadAsArray());
        }

        try {
            $handler->handle();
            $scheduled->markAsRun();
        } catch (Throwable $e) {
            $scheduled->markAsFailed($e->getMessage());
            throw $e;
        }
    }
}
