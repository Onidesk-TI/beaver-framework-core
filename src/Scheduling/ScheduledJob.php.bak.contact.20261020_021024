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

use Beaver\Database\Model\Model;

/**
 * ScheduledJob — Model de um job agendado.
 *
 * Representa uma linha na tabela `scheduled_jobs`. Cada plugin
 * declara os seus jobs no `plugin.json` (secção `scheduled_jobs`),
 * e o Beaver regista-os aqui.
 */
class ScheduledJob extends Model
{
    protected static string $table = 'scheduled_jobs';

    protected array $fillable = [
        'name',
        'description',
        'handler',
        'payload',
        'cron_expression',
        'enabled',
        'is_active',
        'last_run_at',
        'last_status',
        'last_error',
    ];

    public function payloadAsArray(): array
    {
        if ($this->payload === null || $this->payload === '') {
            return [];
        }

        $data = json_decode((string) $this->payload, true);

        return is_array($data) ? $data : [];
    }

    public function markAsRun(): void
    {
        $this->last_run_at = date('Y-m-d H:i:s');
        $this->last_status = 'ok';
        $this->last_error  = null;
        $this->save();
    }

    public function markAsFailed(string $error): void
    {
        $this->last_run_at = date('Y-m-d H:i:s');
        $this->last_status = 'failed';
        $this->last_error  = $error;
        $this->save();
    }

    /**
     * Jobs que devem correr: ativos E enabled.
     *
     * @return ScheduledJob[]
     */
    public static function active(): array
    {
        return static::where('is_active', 1)
            ->where('enabled', 1)
            ->get();
    }
}
