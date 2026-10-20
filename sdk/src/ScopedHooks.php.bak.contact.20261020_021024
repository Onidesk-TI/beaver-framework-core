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

use Beaver\Plugin\Hooks;

/**
 * Wrapper de Hooks que passa cada operação pelo Context do plugin.
 *
 * Em mode='audit' regista o uso indevido e deixa passar.
 * Em mode='enforce' bloqueia com PermissionDeniedException.
 *
 * Uso:
 *   $scoped = new ScopedHooks($hooks, $context);
 *   $scoped->on('order.placed', fn($o) => ...);   // check hooks.listen
 *   $scoped->emit('sms.sent', $msg);              // check hooks.emit
 */
final class ScopedHooks
{
    public function __construct(
        private Hooks $inner,
        private Context $context,
    ) {}

    /**
     * Registar listener. Verifica 'hooks.listen'.
     */
    public function on(string $event, callable $callback, int $priority = 10): void
    {
        $this->context->check('hooks.listen', "event=$event");
        $this->inner->on($event, $callback, $priority);
    }

    /**
     * Emitir evento. Verifica 'hooks.emit'.
     */
    public function emit(string $event, mixed ...$args): array
    {
        $this->context->check('hooks.emit', "event=$event");
        return $this->inner->emit($event, ...$args);
    }

    /**
     * Aplicar filtro. Verifica 'hooks.listen' (é um listener que devolve valor).
     */
    public function filter(string $event, mixed $value, mixed ...$args): mixed
    {
        $this->context->check('hooks.listen', "filter=$event");
        return $this->inner->filter($event, $value, ...$args);
    }

    /**
     * Existe algum listener? Não verifica permissão (é leitura).
     */
    public function has(string $event): bool
    {
        return $this->inner->has($event);
    }

    /**
     * Acesso direto ao Hooks original (para debug/introspeção).
     * Não uses isto em código de plugin.
     */
    public function inner(): Hooks
    {
        return $this->inner;
    }

    public function context(): Context
    {
        return $this->context;
    }
}
