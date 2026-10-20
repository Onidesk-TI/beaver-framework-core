<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Chave de encriptação
    |--------------------------------------------------------------------------
    | Deve ter 32 bytes (raw ou base64). Vem do APP_KEY do .env.
    |
    | Gerar nova:
    |     php -r "echo base64_encode(random_bytes(32));"
    |
    | ⚠️ Se mudares, os dados encriptados anteriormente ficam inacessíveis.
    */

    // Tenta múltiplas fontes — alguma há de funcionar
    'key' => (function () {
        $candidates = [
            $_ENV['APP_KEY']       ?? null,
            $_SERVER['APP_KEY']    ?? null,
            getenv('APP_KEY')      ?: null,
        ];

        foreach ($candidates as $k) {
            if (is_string($k) && $k !== '') {
                return $k;
            }
        }

        // Fallback: ler direto do .env
        $envFile = dirname(__DIR__) . '/.env';
        if (is_file($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if (str_starts_with($line, 'APP_KEY=')) {
                    $v = trim(substr($line, 8));
                    if (strlen($v) >= 2) {
                        $f = $v[0]; $l = $v[strlen($v) - 1];
                        if (($f === '"' && $l === '"') || ($f === "'" && $l === "'")) {
                            $v = substr($v, 1, -1);
                        }
                    }
                    return $v;
                }
            }
        }

        return '';
    })(),
];
