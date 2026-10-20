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
 * Contexto de execução de um plugin.
 *
 * Sabe quais permissões foram concedidas (granted) e regista
 * tentativas de uso indevido no auditor (em modo audit).
 */
final class Context
{
    /**
     * @param string              $slug        Slug do plugin
     * @param array<int,string>   $granted     Permissões concedidas (normalizadas)
     * @param string              $mode        'off' | 'audit' | 'enforce'
     * @param PermissionAuditor|null $auditor   Registo de eventos (opcional)
     */
    public function __construct(
        public readonly string $slug,
        public readonly array $granted,
        public readonly string $mode = 'audit',
        private ?PermissionAuditor $auditor = null,
    ) {}

    /** O plugin declarou/tem a permissão? */
    public function has(string $scope): bool
    {
        return in_array($scope, $this->granted, true);
    }

    /**
     * Verifica uma permissão. Regista e/ou bloqueia conforme o mode.
     *
     * @param  string $scope   ex: 'db.write'
     * @param  string $detail  descrição opcional (ex: 'tabela=orders')
     * @return bool            true se autorizado
     * @throws PermissionDeniedException  apenas em mode='enforce'
     */
    public function check(string $scope, string $detail = ''): bool
    {
        if ($this->mode === 'off') {
            return true;
        }

        $allowed = $this->has($scope);

        if (!$allowed) {
            $this->auditor?->record(
                slug:   $this->slug,
                scope:  $scope,
                detail: $detail,
                result: $this->mode === 'enforce' ? 'blocked' : 'audit',
            );

            if ($this->mode === 'enforce') {
                throw new PermissionDeniedException(
                    "Plugin '{$this->slug}' tentou usar '{$scope}' sem permissão."
                    . ($detail !== '' ? " ($detail)" : '')
                );
            }
        }

        return $allowed;
    }

    /** Apenas para leitura: lista de permissões declaradas. */
    public function granted(): array
    {
        return $this->granted;
    }

    public function mode(): string
    {
        return $this->mode;
    }
}

