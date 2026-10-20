<?php
/**
 * Configuração das apps mobile: locale, rotas, produtos.
 */
return [
    // ─── i18n ───
    'default_locale'    => 'en',
    'supported_locales' => ['en', 'pt'],
    'locale_detect'     => ['query', 'cookie', 'header'],   // ordem
    'locale_prefix'     => false,                            // true = /m/{locale}/...

    // ─── Roteamento: nomes de asset por locale ───
    // Todas as chaves PT/EN válidas resolvem para o mesmo asset.
    'route_names' => [
        'products' => ['en' => 'products', 'pt' => 'produtos'],
        'cart'     => ['en' => 'cart',     'pt' => 'carrinho'],
        'checkout' => ['en' => 'checkout', 'pt' => 'finalizar'],
        'orders'   => ['en' => 'orders',   'pt' => 'encomendas'],
    ],

    // ─── Produtos: fonte e mapeamento ───
    'products' => [
        'source'     => 'auto',              // 'db' | 'json' | 'auto'
        'db_table'   => 'products',
        'json_file'  => 'products.json',     // fallback PT: produtos.json
    ],

    // ─── Apps / produtos ───
    'default_slug' => 'a-minha-app',
    'products_map' => [],
];
