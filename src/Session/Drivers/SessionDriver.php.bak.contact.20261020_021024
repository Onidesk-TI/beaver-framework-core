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

namespace Beaver\Session\Drivers;

/**
 * Contrato para drivers de sessão.
 */
interface SessionDriver
{
    /** Inicia a sessão (idempotente). */
    public function start(): void;

    /**
     * Devolve todos os dados.
     *
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * Substitui todos os dados.
     *
     * @param array<string, mixed> $data
     */
    public function replace(array $data): void;

    /** Devolve o ID da sessão. */
    public function id(): string;

    /** Regenera o ID (segurança pós-login). */
    public function regenerate(): void;

    /** Destrói a sessão. */
    public function destroy(): void;
}
