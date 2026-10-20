<?php

/**
 * Bootstrap da aplicação.
 *
 * Carrega o autoload do projeto, resolve o caminho do framework
 * e cria a \Beaver\Foundation\Application.
 *
 * Devolve: \Beaver\Foundation\Application
 */

declare(strict_types=1);

use Beaver\Foundation\Application;

//  Autoload do projeto (procurar em vários sítios)
$candidates = [
    __DIR__ . '/../../autoload.php',          // vendor/autoload.php (projeto, framework em vendor/beaverphp/framework/bootstrap/)
    __DIR__ . '/../vendor/autoload.php',       // framework standalone
    __DIR__ . '/../../vendor/autoload.php',    // projeto com vendor/ na raiz
    getcwd() . '/vendor/autoload.php',         // cwd
];

$autoload = null;
foreach ($candidates as $c) {
    if (is_file($c)) {
        $autoload = realpath($c);
        break;
    }
}

if ($autoload === null) {
    fwrite(STDERR, "❌ vendor/autoload.php não encontrado.\n");
    fwrite(STDERR, "   Corre 'composer install' no projeto.\n");
    exit(1);
}

require_once $autoload;

// Caminho do projeto = raiz (uma acima de bootstrap/)
$projectPath = dirname(__DIR__);

//  Caminho do framework (resolvido via vendor/composer/installed.json)
$frameworkPath = require __DIR__ . '/framework-path.php';

// Expor o caminho do framework para o config/app.php o usar
putenv("BEAVER_FRAMEWORK_PATH={$frameworkPath}");
$_ENV['BEAVER_FRAMEWORK_PATH']    = $frameworkPath;
$_SERVER['BEAVER_FRAMEWORK_PATH'] = $frameworkPath;

// Criar Application com os 2 paths
return new Application($projectPath, $frameworkPath);
