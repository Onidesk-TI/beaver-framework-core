<?php
declare(strict_types=1);

/**
 * Catálogo de downloads disponíveis no painel.
 */
return [
    [
        'id'        => 'framework',
        'nome'      => 'Beaver Framework',
        'versao'    => 'v1.4.0',
        'descricao' => 'Framework completo — kernel, router, ORM, view engine.',
        'tamanho'   => '~4 MB',
        'icon'      => '📦',
        'url'       => '/painel/download/framework',
    ],
    [
        'id'        => 'cli',
        'nome'      => 'Beaver CLI',
        'versao'    => 'v1.4.0',
        'descricao' => 'Linha de comandos `beaver` para gerar código e gerir migrações.',
        'tamanho'   => '~1 MB',
        'icon'      => '⚡',
        'url'       => '/painel/download/cli',
    ],
    [
        'id'        => 'sdk',
        'nome'      => 'Beaver SDK',
        'versao'    => 'v1.0.0',
        'descricao' => 'Kit para criar plugins robustos com contratos estáveis.',
        'tamanho'   => '~500 KB',
        'icon'      => '🔌',
        'url'       => '/painel/download/sdk',
    ],
    [
        'id'        => 'mobile',
        'nome'      => 'Beaver Mobile Kit',
        'versao'    => 'v0.9.0',
        'descricao' => 'Componentes móveis, editor visual e templates prontos.',
        'tamanho'   => '~2 MB',
        'icon'      => '📱',
        'url'       => '/painel/download/mobile',
    ],
];
