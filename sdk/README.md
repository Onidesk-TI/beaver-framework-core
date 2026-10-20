# Beaver SDK

**Plugin API v1.0.0** — contrato oficial para desenvolvimento de plugins no Beaver Framework.

## O que é

Camada estável entre o **core** e os **plugins**:

- Define **o que é um plugin válido** (`plugin.json`)
- Define **como o plugin se liga ao core** (classe `*Plugin extends PluginBase`)
- Define **como declarar permissões** (`db.read`, `network.outbound`, ...)
- Oferece **harness de testes** (`PluginTester`, `FakeHooks`)

O `PluginManager` do core usa este SDK para validar cada plugin antes de o instanciar.

## Quick start

    php beaver plugin:make meu-plugin --author="O teu nome"
    php beaver plugin:validate meu-plugin
    php beaver plugin:info meu-plugin
    composer dump-autoload

## Comandos CLI

| Comando | Descrição |
|---|---|
| `plugin:list [--json]` | Lista plugins do mode ativo |
| `plugin:validate [slug] [--json]` | Valida manifestos |
| `plugin:info <slug> [--json]` | Detalhes de um plugin |
| `plugin:make <nome> [--dest=PATH]` | Cria plugin novo |

`--dest=` aceita paths absolutos (Windows e Unix) e relativos.

## Estrutura de um plugin

    namespace Beaver\Plugins\MeuPlugin;

    use Beaver\Plugin\PluginBase;
    use Beaver\Sdk\HooksCatalog;

    class MeuPluginPlugin extends PluginBase
    {
        public function boot(): void
        {
            $this->loadViews();
            $this->loadTranslations();
            $this->loadRoutes();

            $this->hooks()->on(HooksCatalog::ORDER_PLACED, fn($o) => null);
        }
    }

## Testes

    use Beaver\Sdk\Testing\PluginTester;

    $t = PluginTester::for(MeuPluginPlugin::class, __DIR__ . '/../');
    $t->validateManifest();
    $t->boot();
    $this->assertTrue($t->hooks->wasEmitted('plugin.booted'));

## Ver também

- [MANIFEST.md](MANIFEST.md) — referência do `plugin.json`
- [HOOKS.md](HOOKS.md) — catálogo de hooks
- [CHANGELOG.md](CHANGELOG.md) — histórico

**API:** 1.0.0 · **Framework min:** 0.1.0 · **PHP:** 8.1+ · **Licença:** MIT
