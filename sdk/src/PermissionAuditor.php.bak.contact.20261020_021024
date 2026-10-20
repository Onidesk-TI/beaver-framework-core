<?php

declare(strict_types=1);

/**
 * Beaver Framework — Modern PHP framework with a plugin ecosystem.
 *
 * @package    Beaver SDK
 * @version    0.1.0
 * @author     Franco <onidesk@outlook.com>
 * @copyright  2026 Onidesk
 * @license    MIT <https://opensource.org/licenses/MIT>
 * @link       https://github.com/Onidesk-TI/beaver-framework
 */

namespace Beaver\Sdk;

/**
 * Registo de tentativas de uso de permissões (modo audit).
 *
 * Escreve numa linha por evento em storage/logs/plugin-audit.log:
 *
 *   2026-09-21 20:55:01 | sms | db.write | table=orders | AUDIT
 *   2026-09-21 20:55:02 | sms | network.outbound | host=api.x | BLOCKED
 *
 * Não bloqueia nada — só observa. Em mode='enforce', o Context lança
 * exceção antes de chegar aqui com result='blocked'.
 */
final class PermissionAuditor
{
    /** @var array<int,array{slug:string,scope:string,detail:string,result:string,at:float}> */
    private array $events = [];

    public function __construct(
        private ?string $logFile = null,
        private bool $alsoLogToFile = true,
    ) {
        $this->logFile ??= $this->defaultLogPath();
    }

    /**
     * Regista um evento.
     *
     * @param string $slug    Slug do plugin
     * @param string $scope   Permissão pedida (ex: 'db.write')
     * @param string $detail  Detalhe opcional (ex: 'table=orders')
     * @param string $result  'audit' | 'blocked' | 'granted'
     */
    public function record(
        string $slug,
        string $scope,
        string $detail = '',
        string $result = 'audit',
    ): void {
        $event = [
            'slug'   => $slug,
            'scope'  => $scope,
            'detail' => $detail,
            'result' => $result,
            'at'     => microtime(true),
        ];

        $this->events[] = $event;

        if ($this->alsoLogToFile && $this->logFile !== null) {
            $this->appendToFile($event);
        }
    }

    /** Todos os eventos registados nesta execução. */
    public function all(): array
    {
        return $this->events;
    }

    /** Eventos filtrados por plugin. */
    public function forPlugin(string $slug): array
    {
        return array_values(array_filter(
            $this->events,
            static fn (array $e): bool => $e['slug'] === $slug,
        ));
    }

    /** Eventos filtrados por scope. */
    public function forScope(string $scope): array
    {
        return array_values(array_filter(
            $this->events,
            static fn (array $e): bool => $e['scope'] === $scope,
        ));
    }

    /** Resumo agrupado: slug => scope => contagem. */
    public function summary(): array
    {
        $out = [];
        foreach ($this->events as $e) {
            $out[$e['slug']][$e['scope']] =
                ($out[$e['slug']][$e['scope']] ?? 0) + 1;
        }
        return $out;
    }

    /** Limpa eventos em memória (não apaga o ficheiro). */
    public function reset(): void
    {
        $this->events = [];
    }

    public function logFile(): ?string
    {
        return $this->logFile;
    }

    // ------------------------------------------------------------------

    private function appendToFile(array $event): void
    {
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $line = sprintf(
            "%s | %s | %s | %s | %s\n",
            date('Y-m-d H:i:s', (int) $event['at']),
            $event['slug'],
            $event['scope'],
            $event['detail'] !== '' ? $event['detail'] : '-',
            strtoupper($event['result']),
        );

        @file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
    }

    private function defaultLogPath(): ?string
    {
        // Tenta descobrir a pasta storage/ do projeto
        $candidates = [
            getcwd() . '/storage/logs/plugin-audit.log',
            dirname(__DIR__, 3) . '/storage/logs/plugin-audit.log',
        ];

        foreach ($candidates as $c) {
            $dir = dirname($c);
            if (is_dir($dir) || @mkdir($dir, 0775, true)) {
                return $c;
            }
        }

        return null;
    }
}
