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

final class HooksCatalog
{
    // Ciclo de vida
    public const APP_BOOTED    = 'app.booted';
    public const PLUGIN_BOOTED = 'plugin.booted';

    // Encomendas
    public const ORDER_PLACED    = 'order.placed';
    public const ORDER_PAID      = 'order.paid';
    public const ORDER_CANCELLED = 'order.cancelled';
    public const ORDER_SHIPPED   = 'order.shipped';

    // Clientes
    public const CUSTOMER_CREATED = 'customer.created';
    public const CUSTOMER_UPDATED = 'customer.updated';

    // HTTP
    public const HTTP_REQUEST  = 'http.request';
    public const HTTP_RESPONSE = 'http.response';

    // UI / Views
    public const ADMIN_MENU    = 'admin.menu';
    public const VIEW_RENDERED = 'view.rendered';

    /** @return array<string,string> */
    public static function all(): array
    {
        return (new \ReflectionClass(self::class))->getConstants();
    }
}
