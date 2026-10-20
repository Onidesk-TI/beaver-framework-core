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

final class ManifestValidator
{
    private const REQUIRED = ['name', 'slug', 'version', 'namespace', 'main', 'beaver_version'];

    /**
     * Catálogo de permissões conhecidas.
     *
     * Aceita dois formatos no plugin.json:
     *
     *   Plano:        ["db.read", "hooks.listen"]
     *   Rico:         {"filesystem": {"read": ["storage/"]}}
     *
     * No formato rico, só o topo (grupo.tipo) é validado.
     * Os escopos internos (paths, hosts) são metadados livres.
     */
    private const ALLOWED_PERMISSIONS = [
        // Acesso a dados
        'db.read', 'db.write',
        'filesystem.read', 'filesystem.write',
        'fs.read', 'fs.write',
        // Rede
        'network.outbound', 'network.inbound',
        'http.outbound', 'http.inbound',
        // Hooks e eventos
        'hooks.listen', 'hooks.emit',
        // UI e routing
        'routes.register',
        'views.register',
        'views.render',
        'admin.menu',
        // Sistema
        'migrations.run',
        'settings.manage',
        'settings.read',
        // Scheduler / jobs
        'scheduler.register',
        'queue.push',
        // Notificações
        'mail.send',
        'sms.send',
    ];

    /** @return string[] Lista de erros; vazio = válido. */
    public static function validate(array $manifest): array
    {
        $errors = [];

        foreach (self::REQUIRED as $req) {
            if (empty($manifest[$req])) {
                $errors[] = "Campo obrigatório em falta: $req";
            }
        }

        if (!empty($manifest['slug']) && !preg_match('/^[a-z][a-z0-9_-]*$/', $manifest['slug'])) {
            $errors[] = "slug inválido: deve ser kebab-case minúsculo (ex: sms-gateway)";
        }

        if (!empty($manifest['version']) && !preg_match('/^\d+\.\d+\.\d+$/', $manifest['version'])) {
            $errors[] = "version inválida: use semver x.y.z";
        }

        if (!empty($manifest['namespace'])
            && !str_starts_with($manifest['namespace'], 'Beaver\\Plugins\\')) {
            $errors[] = "namespace deve começar por 'Beaver\\Plugins\\'";
        }

        if (!empty($manifest['main']) && !preg_match('/\.php$/', $manifest['main'])) {
            $errors[] = "main deve ser um ficheiro .php";
        }

        if (!empty($manifest['api_version'])
            && version_compare($manifest['api_version'], SdkVersion::API_VERSION, '>')) {
            $errors[] = "api_version ({$manifest['api_version']}) superior à do SDK (" . SdkVersion::API_VERSION . ")";
        }

        if (isset($manifest['permissions'])) {
            if (!is_array($manifest['permissions'])) {
                $errors[] = "permissions deve ser um array";
            } else {
                foreach (self::flattenPermissions($manifest['permissions']) as $p) {
                    if (!is_string($p) || $p === '') {
                        continue;
                    }
                    if (!in_array($p, self::ALLOWED_PERMISSIONS, true)) {
                        $errors[] = "permissão desconhecida: $p";
                    }
                }
            }
        }

        return $errors;
    }

    public static function allowedPermissions(): array
    {
        return self::ALLOWED_PERMISSIONS;
    }

    /**
     * Normaliza permissions para um array plano de strings.
     *
     * Aceita:
     *   ["db.read", "hooks.listen"]                    → igual
     *   ["filesystem" => ["read", "write"]]            → ["filesystem.read", "filesystem.write"]
     *   ["filesystem" => "read"]                       → ["filesystem.read"]
     *
     * @return string[]
     */
    private static function flattenPermissions(array $perms): array
    {
        $flat = [];
        foreach ($perms as $key => $value) {
            if (is_int($key)) {
                // plano: ["db.read", "hooks.listen"]
                if (is_string($value)) {
                    $flat[] = $value;
                }
                continue;
            }

            // rico: "filesystem" => ["read" => [...], "write" => [...]]
            //       "filesystem" => "read"
            //       "db.read"    => true
            if (is_bool($value)) {
                $flat[] = $key;
            } elseif (is_string($value)) {
                // "filesystem" => "read"  → filesystem.read
                $flat[] = $key . '.' . $value;
            } elseif (is_array($value)) {
                // Se for lista sequencial de strings → chave é o tipo completo
                //   "filesystem.read" => ["storage/", "config/"]
                // Se for mapa associativo → recursão
                //   "filesystem" => ["read" => [...], "write" => [...]]
                $isList = array_is_list($value);
                if ($isList) {
                    // escopos do próprio tipo → só o nome importa
                    $flat[] = $key;
                } else {
                    foreach ($value as $subKey => $subValue) {
                        $flat[] = $key . '.' . $subKey;
                    }
                }
            }
        }
        return $flat;
    }
}
