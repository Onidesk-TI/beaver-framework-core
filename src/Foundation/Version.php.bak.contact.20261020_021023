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

declare(strict_types=1);

namespace Beaver\Foundation;

/**
 * Versão do Beaver — fonte única de verdade.
 *
 * Atualizada pelo comando: ./beaver version:bump <nova-versao>
 * NÃO editar manualmente sem correr version:sync a seguir.
 */
final class Version
{
    /**
     * Versão do framework — LIDA do composer.json (fonte única).
     * Cacheada em memória para evitar I/O repetido.
     */
    public static function number(): string
    {
        static $v = null;
        return $v ??= self::readFromComposer() ?? '0.0.0';
    }

    /**
     * Versão da Plugin API / SDK.
     * Independente do framework — só muda quando há breaking changes na API do SDK.
     * Este SIM faz sentido ser constante (muda raramente, é contrato).
     */
    public static function api(): string
    {
        static $v = null;
        return $v ??= self::readApiVersion() ?? '1.0.0';
    }

    public static function name(): string
    {
        return 'Beaver Framework';
    }

    public static function majorMinor(): string
    {
        $p = explode('.', self::number());
        return ($p[0] ?? '0') . '.' . ($p[1] ?? '0');
    }

    private static function readFromComposer(): ?string
    {
        $file = dirname(__DIR__, 2) . '/composer.json';
        if (!is_file($file)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($file), true);
        return !empty($data['version']) ? (string) $data['version'] : null;
    }

    /**
     * A API version vive num sítio separado do composer.json (chave "extra").
     * Ex: {"extra": {"beaver": {"api_version": "1.0.0"}}}
     */
    private static function readApiVersion(): ?string
    {
        $file = dirname(__DIR__, 2) . '/composer.json';
        if (!is_file($file)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($file), true);
        return $data['extra']['beaver']['api_version'] ?? null;
    }
}
