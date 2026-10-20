<?php

/**
 * Beaver Framework — Modern PHP framework with a plugin ecosystem.
 *
 * @package    Beaver Framework
 * @author     Franco <onidesk@outlook.com>
 * @copyright  2026 Onidesk
 * @license    MIT <https://opensource.org/licenses/MIT>
 * @link       https://github.com/Onidesk-TI/beaver-framework
 *
 * ──────────────────────────────────────────────────────────────
 * API HÍBRIDA — três formas de usar
 * ──────────────────────────────────────────────────────────────
 *
 *   1) Helper global (uso comum)
 *         cookie_set('theme', 'dark', 86400);
 *         cookie_get('theme');
 *
 *   2) Container (controlo)
 *         $cookies = app()->make(CookieJar::class);
 *         $cookies->set('key', 'value', 3600);
 *
 *   3) Injeção de dependências
 *         public function __construct(private CookieJar $cookies) {}
 */

namespace Beaver\Cookies;

class CookieJar
{
    /** @var array<string, string>  Cache local de cookies (lidos + escritos) */
    private array $cookies = [];

    /** @var array<int, Cookie>  Cookies pendentes para enviar no save() */
    private array $queued = [];

    /** @var array<string, mixed>  Opções default */
    private array $defaults;

    /**
     * @param array<string, mixed> $defaults
     */
    public function __construct(array $defaults = [])
    {
        $this->defaults = array_merge([
            'path'     => '/',
            'domain'   => null,
            'secure'   => false,
            'httponly' => true,
            'samesite' => 'Lax',
            'ttl'      => 0,
        ], $defaults);

        // Carregar cookies existentes do request
        foreach ($_COOKIE as $name => $value) {
            $this->cookies[(string) $name] = (string) $value;
        }
    }

    /**
     * Lê um cookie.
     */
    public function get(string $name, ?string $default = null): ?string
    {
        return $this->cookies[$name] ?? $default;
    }

    /**
     * Existe?
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->cookies);
    }

    /**
     * Devolve todos os cookies.
     *
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->cookies;
    }

    /**
     * Define um cookie.
     *
     * @param array<string, mixed> $options
     */
    public function set(string $name, string $value, ?int $ttl = null, array $options = []): void
    {
        $ttl = $ttl ?? (int) $this->defaults['ttl'];

        $cookie = new Cookie(
            name:     $name,
            value:    $value,
            expires:  $ttl > 0 ? time() + $ttl : 0,
            path:     $options['path']     ?? $this->defaults['path'],
            domain:   $options['domain']   ?? $this->defaults['domain'],
            secure:   (bool) ($options['secure']   ?? $this->defaults['secure']),
            httponly: (bool) ($options['httponly'] ?? $this->defaults['httponly']),
            samesite: $options['samesite'] ?? $this->defaults['samesite'],
        );

        $this->queued[] = $cookie;

        // Sincronizar cache local (para gets na mesma request)
        $this->cookies[$name] = $value;
    }

    /**
     * Apaga um cookie.
     */
    public function forget(string $name, array $options = []): void
    {
        $cookie = Cookie::forget(
            name:   $name,
            path:   $options['path']   ?? $this->defaults['path'],
            domain: $options['domain'] ?? $this->defaults['domain'],
        );

        $this->queued[] = $cookie;

        unset($this->cookies[$name]);
    }

    /**
     * Envia todos os cookies pendentes.
     *
     * Chamado pelo Kernel no fim da request.
     */
    public function save(): void
    {
        foreach ($this->queued as $cookie) {
            $cookie->send();
        }

        $this->queued = [];
    }

    /**
     * Nº de cookies pendentes.
     */
    public function pendingCount(): int
    {
        return count($this->queued);
    }
}
