<?php

/**
 * Beaver Framework — ThemeValidator
 *
 * Valida um theme.json contra as regras do theme.schema.json.
 * Validação em PHP puro (sem dependências externas), com mensagens
 * de erro claras.
 *
 * @package    Beaver\Sdk\Theme
 * @version    0.1.0
 * @author     Franco <onidesk@outlook.com>
 * @copyright  2026 Onidesk
 * @license    MIT <https://opensource.org/licenses/MIT>
 */

declare(strict_types=1);

namespace Beaver\Sdk\Theme;

final class ThemeValidator
{
    /** Campos obrigatórios. */
    private const REQUIRED = ['name', 'slug', 'version', 'beaver_version'];

    /** Campos permitidos. Qualquer outro é rejeitado. */
    private const ALLOWED = [
        'name', 'slug', 'version', 'beaver_version', 'api_version',
        'author', 'description', 'license', 'homepage', 'screenshot',
        'parent', 'colors', 'supports', 'assets',
    ];

    /**
     * Valida um manifest e devolve lista de erros.
     * Lista vazia = válido.
     *
     * @param  array $manifest
     * @return string[]
     */
    public static function validate(array $manifest): array
    {
        $errors = [];

        //  Campos obrigatórios
        foreach (self::REQUIRED as $field) {
            if (!array_key_exists($field, $manifest)) {
                $errors[] = "Campo obrigatório em falta: '$field'";
            } elseif (!is_string($manifest[$field]) || $manifest[$field] === '') {
                $errors[] = "Campo '$field' deve ser uma string não vazia";
            }
        }

        //  Campos desconhecidos (additionalProperties: false)
        foreach (array_keys($manifest) as $key) {
            if (!in_array($key, self::ALLOWED, true)) {
                $errors[] = "Campo desconhecido: '$key'";
            }
        }

        //  name
        if (isset($manifest['name']) && is_string($manifest['name'])) {
            $len = mb_strlen($manifest['name']);
            if ($len < 2 || $len > 60) {
                $errors[] = "Campo 'name' deve ter entre 2 e 60 caracteres";
            }
        }

        //  slug
        if (isset($manifest['slug']) && is_string($manifest['slug'])) {
            if (!preg_match('/^[a-z][a-z0-9_-]*$/', $manifest['slug'])) {
                $errors[] = "Campo 'slug' inválido: deve começar por letra minúscula e conter apenas [a-z0-9_-]";
            }
        }

        //  version (X.Y.Z)
        if (isset($manifest['version']) && is_string($manifest['version'])) {
            if (!preg_match('/^\d+\.\d+\.\d+$/', $manifest['version'])) {
                $errors[] = "Campo 'version' deve seguir o formato X.Y.Z (ex: 1.0.0)";
            }
        }

        //  api_version (opcional, mas se existir X.Y.Z)
        if (array_key_exists('api_version', $manifest)) {
            if (!is_string($manifest['api_version'])) {
                $errors[] = "Campo 'api_version' deve ser string";
            } elseif (!preg_match('/^\d+\.\d+\.\d+$/', $manifest['api_version'])) {
                $errors[] = "Campo 'api_version' deve seguir o formato X.Y.Z (ex: 1.0.0)";
            }
        }

        //  description (opcional, max 200)
        if (array_key_exists('description', $manifest)) {
            if (!is_string($manifest['description'])) {
                $errors[] = "Campo 'description' deve ser string";
            } elseif (mb_strlen($manifest['description']) > 200) {
                $errors[] = "Campo 'description' não pode exceder 200 caracteres";
            }
        }

        //  author, license, homepage, screenshot (opcional, string)
        foreach (['author', 'license', 'homepage', 'screenshot'] as $field) {
            if (array_key_exists($field, $manifest) && !is_string($manifest[$field])) {
                $errors[] = "Campo '$field' deve ser string";
            }
        }

        //  parent (opcional, string|null)
        if (array_key_exists('parent', $manifest)) {
            $p = $manifest['parent'];
            if ($p !== null && !is_string($p)) {
                $errors[] = "Campo 'parent' deve ser string ou null";
            } elseif (is_string($p) && isset($manifest['slug']) && $p === $manifest['slug']) {
                $errors[] = "Campo 'parent' não pode ser igual ao próprio 'slug'";
            }
        }

        // colors (opcional, object<string,string>)
        if (array_key_exists('colors', $manifest)) {
            if (!is_array($manifest['colors']) || array_is_list($manifest['colors'])) {
                $errors[] = "Campo 'colors' deve ser um objeto (chave → valor)";
            } else {
                foreach ($manifest['colors'] as $k => $v) {
                    if (!is_string($v)) {
                        $errors[] = "Campo 'colors.$k' deve ser string";
                    }
                }
            }
        }

        //  supports (opcional, array<string> único)
        if (array_key_exists('supports', $manifest)) {
            if (!is_array($manifest['supports']) || !array_is_list($manifest['supports'])) {
                $errors[] = "Campo 'supports' deve ser um array";
            } else {
                foreach ($manifest['supports'] as $s) {
                    if (!is_string($s)) {
                        $errors[] = "Campo 'supports' deve conter apenas strings";
                        break;
                    }
                }
                if (count($manifest['supports']) !== count(array_unique($manifest['supports']))) {
                    $errors[] = "Campo 'supports' não pode ter valores duplicados";
                }
            }
        }

        //  assets (opcional, object)
        if (array_key_exists('assets', $manifest)) {
            if (!is_array($manifest['assets']) || array_is_list($manifest['assets'])) {
                $errors[] = "Campo 'assets' deve ser um objeto";
            }
        }

        return $errors;
    }

    /**
     * Valida e devolve o manifest ou lança exceção.
     *
     * @throws \InvalidArgumentException
     */
    public static function validateOrFail(array $manifest): ThemeManifest
    {
        $errors = self::validate($manifest);

        if ($errors !== []) {
            throw new \InvalidArgumentException(
                "theme.json inválido:\n - " . implode("\n - ", $errors)
            );
        }

        return ThemeManifest::fromArray($manifest);
    }
}
