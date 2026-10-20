<?php

declare(strict_types=1);

/**
 * Beaver Framework — Middleware RequireAuth
 *
 * @package    Beaver Framework
 * @license    MIT <https://opensource.org/licenses/MIT>
 */

namespace Beaver\Http\Middleware;

use Beaver\Auth\Auth;
use Beaver\Http\Request;
use Beaver\Http\Response;

/**
 * Bloqueia o acesso se não houver utilizador autenticado.
 *
 * Uso nas rotas:
 *   $router->get('/admin', [$ctrl, 'index'], [RequireAuth::class]);
 *
 * Redireciona para /login?next=<uri> por omissão.
 */
class RequireAuth
{
    public function __construct(
        private string $loginUrl = '/login',
    ) {}

    public function handle(Request $request, callable $next): Response
    {
        if (Auth::check()) {
            return $next($request);
        }

        // Se for pedido AJAX/JSON → 401 com JSON
        if ($this->wantsJson($request)) {
            return Response::json([
                'error'   => 'unauthenticated',
                'message' => 'É necessário autenticar.',
            ], 401);
        }

        // Caso contrário, redirect para /login?next=<uri original>
        $target = $request->uri ?? '/';
        $url = $this->loginUrl . '?next=' . urlencode($target);

        return Response::redirect($url);
    }

    private function wantsJson(Request $request): bool
    {
        $accept = $request->header('Accept') ?? '';
        $xrw    = $request->header('X-Requested-With') ?? '';

        return str_contains($accept, 'application/json')
            || strtolower($xrw) === 'xmlhttprequest';
    }
}
