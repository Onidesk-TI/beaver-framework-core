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

use Beaver\Foundation\Application;

class Kernel
{
    public function __construct(private Application $app)
    {
    }

    public function handle(Request $request): Response
    {
        try {
            $response = Router::current()->dispatch($request);
            $this->saveState();
            return $response;
        } catch (\Throwable $e) {
            $this->saveState();
            return $this->handleException($e);
        }
    }

    private function saveState(): void
    {
        try {
            $this->app->make(\Beaver\Session\Session::class)->save();
        } catch (\Throwable) {
            // Session não disponível — ignorar silenciosamente
        }

        try {
            $this->app->make(\Beaver\Cookies\CookieJar::class)->save();
        } catch (\Throwable) {
            // CookieJar não disponível — ignorar silenciosamente
        }
    }


    private function handleException(\Throwable $e): Response
    {
        return $this->app->make(\Beaver\Error\ErrorRenderer::class)
         ->renderException($e);
    }
}
