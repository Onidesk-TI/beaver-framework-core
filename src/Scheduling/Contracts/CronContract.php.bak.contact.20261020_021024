<?php

/**
 * Beaver Framework — Modern PHP framework with a plugin ecosystem.
 *
 * @package    Beaver Framework
 * @author     Franco <onidesk@outlook.com>
 * @copyright  2026 Onidesk
 * @license    MIT <https://opensource.org/licenses/MIT>
 * @link       https://github.com/Onidesk-TI/beaver-framework
 */

namespace Beaver\Scheduling\Contracts;

/**
 * Contrato para drivers de cron (por sistema operativo).
 *
 * Cada SO tem o seu mecanismo:
 *  - Linux:   crontab / /etc/cron.d/
 *  - Windows: Task Scheduler (schtasks)
 *  - macOS:   crontab (ou launchd)
 *
 * Este contrato abstrai essas diferenças — o CronManager chama
 * os métodos e cada driver trata do que é específico do SO.
 */
interface CronContract
{
    /** Adiciona (ou substitui) um job. */
    public function addJob(string $name, string $expression, string $command): void;

    /** Remove um job pelo nome. */
    public function removeJob(string $name): void;

    /** Devolve todos os jobs registados (name => ['expression' => ..., 'command' => ...]). */
    public function listJobs(): array;

    /** Substitui todos os jobs de uma vez. */
    public function sync(array $jobs): void;

    /** Nome do driver (para debug/logs). */
    public function driverName(): string;
}
