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

class Router
{
    private array $routes = [];
    private array $groupStack = [];
    private static ?self $current = null;

    public function __construct()
    {
        self::$current = $this;
    }

    public static function current(): self
    {
        return self::$current ??= new self();
    }

    // ---------- registo ----------

    public function add(string $method, string $path, mixed $handler, array $middleware = []): void
    {
        $path = $this->applyGroupPrefix($path);
        $middleware = array_merge($this->groupMiddleware(), $middleware);

        $this->routes[] = [
            'method'     => strtoupper($method),
            'path'       => $this->normalize($path),
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    public function get(string $p, mixed $h, array $mw = []): void
    {
        $this->add('GET', $p, $h, $mw);
    }
    public function post(string $p, mixed $h, array $mw = []): void
    {
        $this->add('POST', $p, $h, $mw);
    }
    public function put(string $p, mixed $h, array $mw = []): void
    {
        $this->add('PUT', $p, $h, $mw);
    }
    public function patch(string $p, mixed $h, array $mw = []): void
    {
        $this->add('PATCH', $p, $h, $mw);
    }
    public function delete(string $p, mixed $h, array $mw = []): void
    {
        $this->add('DELETE', $p, $h, $mw);
    }

    public function group(array $attrs, callable $callback): void
    {
        $this->groupStack[] = $attrs;
        $callback($this);
        array_pop($this->groupStack);
    }

    // ---------- dispatch ----------

    public function dispatch(Request $request): Response
    {
        $method = strtoupper($request->method) === 'HEAD'
        ? 'GET'
        : $request->method;
        $uri    = $this->normalize($request->uri);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->match($route['path'], $uri);
            if ($params === null) {
                continue;
            }

            return $this->runPipeline($route, $request, $params);
        }

                return app()
                ->make(\Beaver\Error\ErrorRenderer::class)
                ->render404($uri);

                
    }

    private function match(string $routePath, string $uri): ?array
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';

        if (!preg_match($pattern, $uri, $m)) {
            return null;
        }

        return array_filter($m, fn($k) => !is_int($k), ARRAY_FILTER_USE_KEY);
    }

    private function runPipeline(array $route, Request $request, array $params): Response
    {
        $handler = fn(Request $req) => $this->callHandler($route['handler'], $req, $params);

        foreach (array_reverse($route['middleware']) as $mw) {
            $next = $handler;
            $handler = function (Request $req) use ($mw, $next) {
                if (is_string($mw) && class_exists($mw)) {
                    return (new $mw())->handle($req, $next);
                }
                if (is_callable($mw)) {
                    return $mw($req, $next);
                }
                return $next($req);
            };
        }

        return $handler($request);
    }

    private function callHandler(mixed $handler, Request $request, array $params): Response
    {
        $args = array_values($params);

        if (is_callable($handler)) {
            return $this->toResponse($handler($request, ...$args));
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            return $this->toResponse((new $class())->$method($request, ...$args));
        }

        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);
            return $this->toResponse((new $class())->$method($request, ...$args));
        }

        throw new \RuntimeException('Handler inválido: ' . var_export($handler, true));
    }

    private function toResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }
        if (is_array($result)) {
            return Response::json($result);
        }
        if (is_string($result)) {
            return Response::html($result);
        }
        return Response::text((string) $result);
    }

    // ---------- helpers ----------

    private function normalize(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        return rtrim($path, '/') ?: '/';
    }

    private function applyGroupPrefix(string $path): string
    {
        $prefix = '';
        foreach ($this->groupStack as $g) {
            $prefix .= rtrim($g['prefix'] ?? '', '/');
        }
        return $prefix . '/' . ltrim($path, '/');
    }

    private function groupMiddleware(): array
    {
        $mw = [];
        foreach ($this->groupStack as $g) {
            if (!empty($g['middleware'])) {
                $mw = array_merge($mw, (array) $g['middleware']);
            }
        }
        return $mw;
    }

    public function routes(): array
    {
        return $this->routes;
    }
}
