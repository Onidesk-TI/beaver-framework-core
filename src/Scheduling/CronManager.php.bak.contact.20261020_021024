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

namespace Beaver\Scheduling;

use Beaver\Scheduling\Contracts\CronContract;
use Beaver\Scheduling\Drivers\LinuxCron;

/**
 * CronManager — sincroniza os jobs do Beaver com o cron do SO.
 *
 * Lê a tabela `scheduled_jobs`, e escreve no cron do sistema
 * operativo (via o driver adequado).
 *
 * Por agora só tem o driver Linux. Windows e macOS ficam
 * para quando forem testados nesses SO.
 */
class CronManager
{
    private CronContract $driver;

    public function __construct(?CronContract $driver = null)
    {
        $this->driver = $driver ?? $this->resolveDriver();
    }

    /**
     * Sincroniza todos os jobs ativos com o cron do SO.
     * Substitui integralmente o que lá estava.
     */
    public function syncAll(): int
    {
        $jobs = ScheduledJob::active();

        $cronJobs = [];
        foreach ($jobs as $job) {
            $cronJobs[$job->name] = [
                'name'       => $job->name,
                'expression' => $job->cron_expression,
                'command'    => $this->buildCommand($job),
                'enabled'    => true,
            ];
        }

        $this->driver->sync($cronJobs);

        return count($cronJobs);
    }

    public function driver(): CronContract
    {
        return $this->driver;
    }

    private function buildCommand(ScheduledJob $job): string
    {
        $php    = PHP_BINARY;
        $beaver = dirname(__DIR__, 2) . '/beaver';

        return sprintf(
            '%s %s job:run %s',
            escapeshellarg($php),
            escapeshellarg($beaver),
            escapeshellarg($job->name),
        );
    }

    private function resolveDriver(): CronContract
    {
        return match (PHP_OS_FAMILY) {
            'Linux' => new LinuxCron(
                runUser: $this->resolveCronUser(),
            ),
            default => throw new \RuntimeException(
                'Cron driver not implemented for: ' . PHP_OS_FAMILY
            ),
        };
    }

    /**
     * User com que o cron do SO corre os jobs.
     * Ordem: config('cron.user') → env BEAVER_CRON_USER → null (deixa o LinuxCron decidir).
     */
    private function resolveCronUser(): ?string
    {
        if (function_exists('config')) {
            $user = config('cron.user');
            if (is_string($user) && $user !== '') {
                return $user;
            }
        }

        return null;
    }
}
