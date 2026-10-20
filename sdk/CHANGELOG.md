# Changelog — Beaver SDK

Formato: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
Versionamento: [SemVer](https://semver.org/).

## [1.0.0] — Plugin API estabilizada

### Adicionado

- `Manifest` — Value Object tipado
- `ManifestValidator` — validação com mensagens claras
- `SdkVersion` — `API_VERSION`, `MIN_FRAMEWORK`, `satisfies()`
- `PluginPaths` — descoberta unificada de paths
- `HooksCatalog` — catálogo de hooks conhecidos
- `Testing\PluginTester` — harness de testes
- `Testing\FakeHooks` — Hooks em memória
- `schema/plugin.schema.json` — JSON Schema draft 2020-12

### Comandos CLI

- `plugin:list`, `plugin:validate`, `plugin:info`, `plugin:make`
- Todos com `--json`
- `plugin:make --dest=` para criar fora do framework

### Integração com o core

- `PluginManager::instantiate()` valida antes de instanciar
- Plugins inválidos são rejeitados com erro claro

### Cross-platform

- Normalização de paths Windows e Unix
- `beaver` é PHP puro (Linux, macOS, Windows)

### Corrigido

- `Array to string conversion` em permissões ricas
- Slugs com hífen passam validação

## [Unreleased]

### Planeado

- Enforcement real de permissões
- `plugin:enable` / `plugin:disable`
- `plugin:make --template=`
- Assinatura criptográfica de plugins
