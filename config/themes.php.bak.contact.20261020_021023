<?php

/**
 * Beaver Framework — Modern PHP framework with a plugin ecosystem.
 *
 * @package    Beaver Framework
 * @version    0.1.0
 * @author     Franco <onidesk@outlook.com>
 * @copyright  2026 Onidesk
 * @license    GPL-3.0-or-later <https://www.gnu.org/licenses/gpl-3.0.txt>
 * @link       https://github.com/Onidesk-TI/beaver-framework
 */

// config/themes.php — configuração do sistema de temas

return [

    /*
    |--------------------------------------------------------------------------
    | Temas
    |--------------------------------------------------------------------------
    | mode controla de onde os temas são carregados:
    |   dev     → dev_path + staging + prod
    |   staging → staging_path + prod
    |   prod    → apenas path (ignora dev/staging)
    */
    'mode'          => getenv('BEAVER_THEME_MODE') ?: 'prod',

    // Produção (publicados, dentro do framework)
    'path'          => dirname(__DIR__) . '/app/themes',

    // NOVO: temas internos do framework (fazem parte do Beaver)
    'internal_path' => getenv('BEAVER_FRAMEWORK_PATH') ?: dirname(__DIR__) . '/themes',

    // Desenvolvimento (fora do framework — configurável via BEAVER_DEV_THEMES_PATH)
    'dev_path'      => getenv('BEAVER_DEV_THEMES_PATH') ?: null,

    // Staging (opcional, dentro do framework)
    'staging_path'  => dirname(__DIR__) . '/app/themes/.staging',

    // Skeleton (fallback de views/assets)
    // Procura dentro do framework instalado: <framework>/plugins/beaver-skeleton/resources
    'skeleton_path' => (getenv('BEAVER_FRAMEWORK_PATH') ?: dirname(__DIR__))
                        . '/plugins/beaver-skeleton/resources',

    // Estado do tema ativo
    'storage'       => dirname(__DIR__) . '/storage/themes.json',

    // Tema ativo por defeito (null = skeleton puro)
    'default'       => getenv('BEAVER_THEME_DEFAULT') ?: null,

];
