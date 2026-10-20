<?php

/**
 * Beaver Framework — Modern PHP framework with a plugin ecosystem.
 *
 * @package    Beaver Framework
 * @author     Franco <onidesk@outlook.com>
 * @copyright  2026 Onidesk
 * @license    MIT <https://opensource.org/licenses/MIT>
 * @link       https://github.com/Onidesk-TI/beaver-framework
 */

namespace Beaver\Http;

class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $uri,
        public readonly array $query,
        public readonly array $body,
        public readonly array $headers,
        public readonly array $server,
        public readonly array $files,
        public readonly array $cookies,
    ) {
    }

    public static function capture(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        $script = $_SERVER['SCRIPT_NAME'] ?? '';

        // Só aplicar base path se SCRIPT_NAME termina em .php (web server normal).
        // O php -S define SCRIPT_NAME como o path do pedido quando tem extensão,
        // o que causaria truncamento errado.
        if (preg_match('#\.php$#i', $script)) {
            $base = rtrim(dirname($script), '/\\');
            if ($base !== '' && $base !== '/' && str_starts_with($uri, $base)) {
                $uri = substr($uri, strlen($base));
            }
        }

        $uri = '/' . ltrim($uri, '/');

        $body = $_POST;
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $raw  = file_get_contents('php://input');
            $json = json_decode((string) $raw, true);
            $body = is_array($json) ? $json : [];
        }

        return new self(
            method:  $method,
            uri:     $uri,
            query:   $_GET,
            body:    $body,
            headers: self::collectHeaders(),
            server:  $_SERVER,
            files:   $_FILES,
            cookies: $_COOKIE,
        );
    }

    private static function collectHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $k => $v) {
            if (str_starts_with($k, 'HTTP_')) {
                $name = str_replace('_', '-', substr($k, 5));
                $headers[$name] = $v;
            }
        }
        return $headers;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $name = strtoupper(str_replace('-', '_', $name));
        return $this->headers[$name] ?? $default;
    }

    public function isMethod(string $method): bool
    {
        return strtoupper($method) === $this->method;
    }

    public function isJson(): bool
    {
        return str_contains($this->header('Content-Type', '') ?? '', 'application/json');
    }
}
