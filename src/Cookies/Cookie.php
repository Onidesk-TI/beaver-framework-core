<?php

declare(strict_types=1);

namespace Beaver\Cookies;

/**
 * Value object que representa um cookie.
 */
final class Cookie
{
    public function __construct(
        public readonly string $name,
        public readonly string $value,
        public readonly int $expires = 0,
        public readonly string $path = '/',
        public readonly ?string $domain = null,
        public readonly bool $secure = false,
        public readonly bool $httponly = true,
        public readonly string $samesite = 'Lax',
    ) {
    }

    /**
     * Cria um cookie que apaga o existente (expira no passado).
     */
    public static function forget(
        string $name,
        string $path = '/',
        ?string $domain = null,
    ): self {
        return new self(
            name:     $name,
            value:    '',
            expires:  time() - 3600,
            path:     $path,
            domain:   $domain,
        );
    }

    /**
     * Envia o cookie via setcookie().
     */
    public function send(): void
    {
        $options = [
            'expires'  => $this->expires,
            'path'     => $this->path,
            'secure'   => $this->secure,
            'httponly' => $this->httponly,
            'samesite' => $this->samesite,
        ];

        if ($this->domain !== null) {
            $options['domain'] = $this->domain;
        }

        @setcookie($this->name, $this->value, $options);
    }
}
