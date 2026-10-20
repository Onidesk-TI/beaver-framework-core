<?php

/**
 * Bootstrap do CLI (beaver).
 *
 * Carrega a Application e arranca o Console\Application.
 *
 * Uso: chamado pelo wrapper ./beaver
 */

declare(strict_types=1);

use Beaver\Console\Application as Console;
use Beaver\Foundation\Application as Foundation;

//  Bootstrap HTTP partilhado (autoload + Application)
try {
    /** @var Foundation $app */
    $app = require __DIR__ . '/app.php';
} catch (\Throwable $e) {
    fwrite(STDERR, "Erro no bootstrap: {$e->getMessage()}\n");
    exit(1);
}

//  Paths para o Console
$projectPath   = dirname(__DIR__);
$frameworkPath = $app->frameworkPath;

// Arrancar CLI
$console = new Console($frameworkPath, $projectPath);
exit($console->run($argv));
