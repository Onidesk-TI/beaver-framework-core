# Beaver Framework

[![Packagist Version](https://img.shields.io/packagist/v/beaverphp/framework?style=flat-square&color=E07800)](https://packagist.org/packages/beaverphp/framework)
![Tests](https://github.com/Onidesk-TI/beaver-framework-core/actions/workflows/tests.yml/badge.svg)

Modern PHP framework with a plugin ecosystem.

## Instalação

composer require beaverphp/framework

## Requisitos

- PHP >= 8.1
- Composer

## O que inclui

- Core — Router, Container, Auth, Cache, Database, Http, I18n, Logging.
- SDK de plugins — Manifest, ManifestValidator, PluginTester, FakeHooks, PermissionAuditor.
- 34 testes — 23 da framework + 11 do SDK, todos a passar (PHPUnit 12.5).

## Testes

composer install
vendor/bin/phpunit

Output esperado: OK (34 tests, 61 assertions)

## Links

- [Packagist](https://packagist.org/packages/beaverphp/framework)
- [GitHub](https://github.com/Onidesk-TI/beaver-framework-core)

## Licença

MIT
