# Catálogo de hooks

Usa as constantes de `Beaver\Sdk\HooksCatalog`.

    use Beaver\Sdk\HooksCatalog;
    $this->hooks()->on(HooksCatalog::ORDER_PLACED, fn($o) => null);

## Ciclo de vida

| Constante | Valor |
|---|---|
| `APP_BOOTED` | `app.booted` |
| `PLUGIN_BOOTED` | `plugin.booted` |

## Encomendas

| Constante | Valor |
|---|---|
| `ORDER_PLACED` | `order.placed` |
| `ORDER_PAID` | `order.paid` |
| `ORDER_CANCELLED` | `order.cancelled` |
| `ORDER_SHIPPED` | `order.shipped` |

## Clientes

| Constante | Valor |
|---|---|
| `CUSTOMER_CREATED` | `customer.created` |
| `CUSTOMER_UPDATED` | `customer.updated` |

## HTTP

| Constante | Valor |
|---|---|
| `HTTP_REQUEST` | `http.request` |
| `HTTP_RESPONSE` | `http.response` |

## UI / Views

| Constante | Valor |
|---|---|
| `ADMIN_MENU` | `admin.menu` |
| `VIEW_RENDERED` | `view.rendered` |

## Filtros

    $this->hooks()->filter('product.price', fn($p) => $p * 1.1);

## Hooks próprios

Emite com prefixo do teu slug:

    $this->hooks()->emit('sms.sent', $message);

## Prioridade

    $this->hooks()->on('order.placed', $cb, priority: 5);

Menor = corre primeiro. Default `10`.

## Ver também

- `Beaver\Sdk\HooksCatalog`
- `Beaver\Plugin\Hooks`
- `Beaver\Sdk\Testing\FakeHooks`
