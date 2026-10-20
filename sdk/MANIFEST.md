# Referência do plugin.json

Manifesto obrigatório de cada plugin. Validado por `ManifestValidator`.

## Estrutura

    {
        "name":           "Sms Gateway",
        "slug":           "sms-gateway",
        "version":        "1.0.0",
        "author":         "Onidesk",
        "description":    "Envio de SMS",
        "namespace":      "Beaver\\Plugins\\SmsGateway",
        "main":           "src/SmsGatewayPlugin.php",
        "beaver_version": ">=0.1.0",
        "api_version":    "1.0.0",
        "requires":       {"php": ">=8.1", "extensions": ["curl"]},
        "permissions":    ["db.read", "hooks.listen"],
        "admin_menu":     {"label": "SMS", "icon": "fa-comment-sms", "order": 10},
        "routes":         "routes/web.php"
    }

## Obrigatórios

| Campo | Tipo | Exemplo |
|---|---|---|
| `name` | string | `"Sms Gateway"` |
| `slug` | string (kebab-case) | `"sms-gateway"` |
| `version` | string (semver) | `"1.0.0"` |
| `namespace` | string (`Beaver\Plugins\X`) | `"Beaver\\Plugins\\SmsGateway"` |
| `main` | string (ficheiro `.php`) | `"src/SmsGatewayPlugin.php"` |
| `beaver_version` | string | `">=0.1.0"` |

## Opcionais

`author`, `description`, `api_version`, `requires`, `permissions`, `admin_menu`, `routes`.

## Permissões — catálogo

| Grupo | Permissões |
|---|---|
| Dados | `db.read`, `db.write`, `filesystem.read`, `filesystem.write`, `fs.read`, `fs.write` |
| Rede | `network.outbound`, `network.inbound`, `http.outbound`, `http.inbound` |
| Hooks | `hooks.listen`, `hooks.emit` |
| UI/Routing | `routes.register`, `views.register`, `views.render`, `admin.menu` |
| Sistema | `migrations.run`, `settings.read`, `settings.manage` |
| Background | `scheduler.register`, `queue.push` |
| Notificações | `mail.send`, `sms.send` |

## Dois formatos de permissions

Plano:

    "permissions": ["db.read", "hooks.listen"]

Rico (com escopos):

    "permissions": {
        "filesystem": {"read": ["storage/"], "write": ["storage/logs/"]},
        "network":    {"outbound": [{"host": "api.example.com", "port": 443}]}
    }

## Validação

    php beaver plugin:validate meu-plugin

Ver [schema/plugin.schema.json](schema/plugin.schema.json) para o schema formal.
