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

namespace Beaver\Scheduling\Common;

use Beaver\Scheduling\Contracts\CronContract;

/**
 * Base comum para todos os drivers de cron.
 *
 * Implementa a lógica partilhada (gestão do array de jobs,
 * validação, sync) e deixa o "read/write no sistema" para
 * as subclasses (load() e persist()).
 */
abstract class AbstractCron implements CronContract
{
    /** @var array<string, CronJob> */
    protected array $jobs = [];

    public function addJob(string $name, string $expression, string $command): void
    {
        $this->validate($name, $expression, $command);

        $this->jobs[$name] = new CronJob($name, $expression, $command);
        $this->persist();
    }

    public function removeJob(string $name): void
    {
        unset($this->jobs[$name]);
        $this->persist();
    }

    public function listJobs(): array
    {
        $this->load();
        return $this->jobs;
    }

    public function sync(array $jobs): void
    {
        $this->jobs = [];

        foreach ($jobs as $name => $data) {
            if ($data instanceof CronJob) {
                $this->jobs[$data->name] = $data;
                continue;
            }

            if (is_array($data)) {
                $data['name'] = $data['name'] ?? $name;
                $this->jobs[$name] = CronJob::fromArray($data);
            }
        }

        $this->persist();
    }

    // ---- Validação comum ----

    protected function validate(string $name, string $expression, string $command): void
    {
        if ($name === '') {
            throw new \InvalidArgumentException('Job name cannot be empty');
        }

        if (!preg_match('/^[a-zA-Z0-9_\-:]+$/', $name)) {
            throw new \InvalidArgumentException(
                "Job name can only contain letters, numbers, _ , - and : (got: {$name})"
            );
        }

        if (trim($command) === '') {
            throw new \InvalidArgumentException('Job command cannot be empty');
        }

        if (!$this->isValidCronExpression($expression)) {
            throw new \InvalidArgumentException(
                "Invalid cron expression: {$expression}"
            );
        }
    }

    /**
     * Valida uma expressão cron de 5 campos:
     *   minuto hora dia mês dia-da-semana
     */
    protected function isValidCronExpression(string $expression): bool
    {
        $parts = preg_split('/\s+/', trim($expression));

        if (count($parts) !== 5) {
            return false;
        }

        $patterns = [
            '/^(\*|([0-5]?\d)|(\*\/[0-5]?\d)|([0-5]?\d-[0-5]?\d)|([0-5]?\d(,[0-5]?\d)+))$/',
            '/^(\*|([01]?\d|2[0-3])|(\*\/([01]?\d|2[0-3]))|(([01]?\d|2[0-3])-([01]?\d|2[0-3]))|(([01]?\d|2[0-3])(,([01]?\d|2[0-3]))+))$/',
            '/^(\*|([1-9]|[12]\d|3[01])|(\*\/([1-9]|[12]\d|3[01]))|(([1-9]|[12]\d|3[01])-([1-9]|[12]\d|3[01]))|(([1-9]|[12]\d|3[01])(,([1-9]|[12]\d|3[01]))+))$/',
            '/^(\*|([1-9]|1[0-2])|(\*\/([1-9]|1[0-2]))|(([1-9]|1[0-2])-([1-9]|1[0-2]))|(([1-9]|1[0-2])(,([1-9]|1[0-2]))+))$/',
            '/^(\*|[0-7]|(\*\/[0-7])|([0-7]-[0-7])|([0-7](,[0-7])+))$/',
        ];

        foreach (array_values($patterns) as $i => $pattern) {
            if (!preg_match($pattern, $parts[$i])) {
                return false;
            }
        }

        return true;
    }

    // ---- A implementar por cada driver ----

    abstract protected function load(): void;

    abstract protected function persist(): void;
}
