<?php

/**
 * Resolve o caminho absoluto onde o pacote beaver/framework foi instalado.
 * Lê vendor/composer/installed.json (fonte de verdade do Composer).
 *
 * Devolve: string com o path do framework (sem barra final).
 * Lança: RuntimeException se o framework não estiver instalado.
 */

declare(strict_types=1);

return (function (): string {
    $projectRoot = dirname(__DIR__);

    // 1. Se o PRÓPRIO projeto é o framework (tem src/Foundation/Application.php)
    if (is_file($projectRoot . '/src/Foundation/Application.php')) {
        return $projectRoot;
    }

    // 2. Caso contrário, procurar em vendor/ (projeto consumidor)
    $vendorDir = $projectRoot . "/vendor";

    $json = $vendorDir . "/composer/installed.json";
    if (!is_file($json)) {
        throw new \RuntimeException(
            "vendor/composer/installed.json não encontrado.\n" .
            "Corre: composer install"
        );
    }

    $data = json_decode((string) file_get_contents($json), true);
    if (!is_array($data)) {
        throw new \RuntimeException("installed.json inválido.");
    }

    // Composer 2.x: {"packages": [...]}; Composer 1.x: [...]
    $packages = $data["packages"] ?? $data;

    foreach ($packages as $p) {
        if (($p["name"] ?? "") !== "beaver/framework") {
            continue;
        }

        // Composer 2.1+: install-path é relativo a vendor/composer/
        $installPath = $p["install-path"] ?? null;
        if (is_string($installPath) && $installPath !== "") {
            $abs = realpath($vendorDir . "/composer/" . $installPath);
            if ($abs !== false) {
                return $abs;
            }
            return $vendorDir . "/composer/" . $installPath;
        }

        // Fallback: convenção
        return $vendorDir . "/beaver/framework";
    }

    throw new \RuntimeException(
        "Pacote beaver/framework não encontrado em vendor/.\n" .
        "Corre: composer require beaver/framework"
    );
})();
