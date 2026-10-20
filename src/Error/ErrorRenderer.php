<?php

declare(strict_types=1);

namespace Beaver\Error;

use Beaver\Foundation\Application;
use Beaver\Http\Response;

class ErrorRenderer
{
    public function __construct(
        private ?Application $app = null,
    ) {
        $this->app ??= Application::getInstance();
    }

    public function render(int $code, string $detail = '', array $debugData = []): Response
    {
        $title   = $this->translate("errors.{$code}.title",   self::FALLBACK[$code]['title']   ?? 'Error');
        $message = $detail !== ''
            ? $detail
            : $this->translate("errors.{$code}.message", self::FALLBACK[$code]['message'] ?? 'Something went wrong.');

        $image = "/img/beaver-{$code}.png";
        $debug = (bool) $this->app->config('app.debug', false);

        ob_start();
        require __DIR__ . '/resources/error.php';
        $html = (string) ob_get_clean();

        return Response::html($html, $code);
    }

    public function render404(string $uri = ''): Response
    {
        $tpl = $this->translate(
            'errors.404.path',
            'O caminho <code>:uri</code> não existe.'
        );

        $detail = str_replace(':uri', htmlspecialchars($uri), $tpl);

        return $this->render(404, $detail);
    }

    public function renderException(\Throwable $e): Response
    {
        $debug = (bool) $this->app->config('app.debug', false);

        $debugData = $debug ? [
            'exception' => $e,
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'trace'     => $e->getTraceAsString(),
            'class'     => get_class($e),
        ] : [];

        $detail = $debug ? htmlspecialchars($e->getMessage()) : '';

        return $this->render(500, $detail, $debugData);
    }

    private function translate(string $key, string $fallback): string
    {
        try {
            $value = __($key);

            if (!is_string($value) || $value === '' || $value === $key) {
                return $fallback;
            }

            return $value;
        } catch (\Throwable) {
            return $fallback;
        }
    }

    private const FALLBACK = [
        400 => ['title' => 'Pedido inválido',       'message' => 'O servidor não entendeu o pedido.'],
        401 => ['title' => 'Não autenticado',       'message' => 'Precisas de iniciar sessão.'],
        403 => ['title' => 'Acesso proibido',       'message' => 'Não tens permissão.'],
        404 => ['title' => 'Página não encontrada', 'message' => 'O caminho não existe.'],
        419 => ['title' => 'Sessão expirada',       'message' => 'O token CSRF expirou.'],
        422 => ['title' => 'Dados inválidos',       'message' => 'Verifica os campos.'],
        429 => ['title' => 'Demasiados pedidos',    'message' => 'Aguarda um momento.'],
        500 => ['title' => 'Erro interno',          'message' => 'Algo falhou do lado do servidor.'],
        503 => ['title' => 'Em manutenção',         'message' => 'Voltamos em breve.'],
    ];
}
