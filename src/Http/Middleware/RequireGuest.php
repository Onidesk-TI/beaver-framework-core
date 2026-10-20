<?php

declare(strict_types=1);

/**
 * Beaver Framework — Middleware RequireGuest
 *
 * @package    Beaver Framework
 * @license    MIT <https://opensource.org/licenses/MIT>
 */

namespace Beaver\Http\Middleware;

use Beaver\Auth\Auth;
use Beaver\Http\Request;
use Beaver\Http\Response;

/**
 * Bloqueia o acesso se JÁ houver utilizador autenticado.
 *
 * Útil para /login e /register — se já estás logado, não faz sentido
 * ver estas páginas, redireciona para o dashboard.
 *
 * Uso:
 *   $router->get('/login', [$ctrl, 'show'], [RequireGuest::class]);
 */
class RequireGuest
{
    public function __construct(
        private string $redirectTo = '/admin',
    ) {}

    public function handle(Request $request, callable $next): Response
    {
        if (Auth::guest()) {
            return $next($request);
        }

        return Response::redirect($this->redirectTo);
    }
}
