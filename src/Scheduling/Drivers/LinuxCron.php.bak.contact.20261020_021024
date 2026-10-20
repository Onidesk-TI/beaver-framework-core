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

namespace Beaver\Scheduling\Drivers;

use Beaver\Scheduling\Common\AbstractCron;
use Beaver\Scheduling\Common\CronJob;

/**
 * Driver de cron para Linux.
 *
 * Escreve em /etc/cron.d/beaver. Este ficheiro é lido
 * automaticamente pelo daemon cron do Linux, sem precisar
 * de "crontab -e" nem de reload.
 *
 * Formato:
 *   <expr> <user> <command> # name=<name>
 */
class LinuxCron extends AbstractCron
{
    private string $cronFile;

    public function __construct(
        string $cronFile = '/etc/cron.d/beaver',
        private ?string $runUser = null,
    ) {
        $this->cronFile = $cronFile;
    }

    public function driverName(): string
    {
        return 'cron';
    }

    protected function load(): void
    {
        $this->jobs = [];

        if (!is_file($this->cronFile)) {
            return;
        }

        $lines = file($this->cronFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || $line[0] === '#') {
                continue;
            }

            if (str_starts_with($line, 'SHELL=') || str_starts_with($line, 'PATH=')) {
                continue;
            }

            if (preg_match('/^(\S+\s+\S+\s+\S+\s+\S+\s+\S+)\s+\S+\s+(.+?)\s+#\s+name=(\S+)/', $line, $m)) {
                $this->jobs[$m[3]] = new CronJob(
                    name:       $m[3],
                    expression: $m[1],
                    command:    $m[2],
                );
            }
        }
    }

    protected function persist(): void
    {
        $lines = [
            '# /etc/cron.d/beaver — gerado pelo Beaver Framework',
            '# Não editar à mão. Alterações são substituídas no próximo sync.',
            'SHELL=/bin/sh',
            'PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin',
            '',
        ];

        $user = $this->resolveRunUser();

        foreach ($this->jobs as $job) {
            $lines[] = sprintf(
                '%s %s %s # name=%s',
                $job->expression,
                $user,
                $job->command,
                $job->name,
            );
        }

        $lines[] = '';

        $dir = dirname($this->cronFile);
        if (!is_dir($dir)) {
            throw new \RuntimeException("Directory does not exist: {$dir}");
        }

        if (@file_put_contents($this->cronFile, implode("\n", $lines) . "\n") === false) {
            throw new \RuntimeException(
                "Could not write to {$this->cronFile}. Check permissions."
            );
        }

        @chmod($this->cronFile, 0644);
    }

    private function resolveRunUser(): string
    {
        // User explícito no construtor
        if ($this->runUser !== null && $this->runUser !== '') {
            return $this->runUser;
        }

        //  Variável de ambiente
        $env = getenv('BEAVER_CRON_USER');
        if ($env !== false && $env !== '') {
            return $env;
        }

        //  User efetivo do processo
        if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
            $info = posix_getpwuid(posix_geteuid());
            if ($info !== false && isset($info['name'])) {
                return $info['name'];
            }
        }

        //  Fallback
        return 'root';
    }
}
