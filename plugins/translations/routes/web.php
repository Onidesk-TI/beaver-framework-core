<?php

/**
 * @var \Beaver\Http\Router $router
 * @var \Beaver\Plugin\PluginBase $plugin
 */

use Beaver\Plugins\Translations\TranslationController;

$tc = new TranslationController();

$router->get('/lang/{locale}', [$tc, 'setLocale']);
$router->get('/lang', [$tc, 'setLocaleJson']);
$router->post('/lang', [$tc, 'setLocaleJson']);
