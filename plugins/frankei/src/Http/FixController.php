<?php

declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Http;

use App\Services\UserService;
use Beaver\Http\Request;
use Beaver\Http\Response;
use Beaver\Foundation\Application;
use Beaver\Plugins\Frankei\Frankei\Fixer\CsrfFixer;
use Beaver\Plugins\Frankei\Frankei\Fixer\XssFixer;

class FixController
{
    private const ALLOWED_TYPES = ['csrf', 'xss'];

    /**
     * POST /api/frankei/preview-fix
     * Body: { type: "csrf", file, line, path }
     */
    public function preview(Request $request): Response
    {
        $user = $this->requireAuth();
        if ($user instanceof Response) {
            return $user;
        }

        $body = $request->body ?? [];
        $type = (string) ($body['type'] ?? '');
        $file = (string) ($body['file'] ?? '');
        $line = (int)    ($body['line'] ?? 0);
        $root = (string) ($body['path'] ?? '');

        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            return Response::json(['ok' => false, 'error' => 'Tipo nao suportado: ' . $type], 422);
        }

        // Resolver o path absoluto
        $file = $this->resolvePath($file, $root);

        if (!$this->isSafePath($file, $root)) {
            return Response::json(['ok' => false, 'error' => 'Ficheiro fora do projeto'], 403);
        }

        if ($type === 'csrf') {
            $fixer = new CsrfFixer();
            return Response::json($fixer->preview($file, $line));
        }

        if ($type === 'xss') {
            $fixer = new XssFixer();
            return Response::json($fixer->preview($file, $line));
        }

        return Response::json(['ok' => false, 'error' => 'Tipo desconhecido'], 422);
    }

    /**
     * Resolve path relativo para absoluto.
     */
    private function resolvePath(string $file, string $root): string
    {
        if (str_starts_with($file, '/')) return $file;
        return rtrim($root, '/') . '/' . ltrim($file, '/');
    }

    /**
     * POST /api/frankei/fix
     * Body: { type: "csrf", file, line, path, backup?: true }
     */
    public function apply(Request $request): Response
    {
        $user = $this->requireAuth();
        if ($user instanceof Response) return $user;

        $body = $request->body ?? [];
        $type = (string) ($body['type'] ?? '');
        $file = (string) ($body['file'] ?? '');
        $line = (int)    ($body['line'] ?? 0);
        $root = (string) ($body['path'] ?? '');
        $backup = (bool) ($body['backup'] ?? true);

        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            return Response::json(['ok' => false, 'error' => 'Tipo nao suportado'], 422);
        }

        // Resolver o path absoluto
        $file = $this->resolvePath($file, $root);

        if (!$this->isSafePath($file, $root)) {
            return Response::json(['ok' => false, 'error' => 'Ficheiro fora do projeto'], 403);
        }

        if ($type === 'csrf') {
            $fixer = new CsrfFixer();
            $result = $fixer->apply($file, $line, $backup);
            return Response::json($result);
        }

        if ($type === 'xss') {
            $fixer = new XssFixer();
            $result = $fixer->apply($file, $line, $backup);
            return Response::json($result);
        }

        return Response::json(['ok' => false, 'error' => 'Tipo desconhecido'], 422);
    }

    /** Devolve o user ou uma Response de erro */
    private function requireAuth(): array|Response
    {
        if (!UserService::autenticado()) {
            return Response::json(['ok' => false, 'error' => 'Nao autenticado'], 401);
        }
        return UserService::atual() ?: [];
    }

    /**
     * Verifica que $file esta dentro de $root.
     */
    private function isSafePath(string $file, string $root): bool
    {
        // Resolver path relativo
        if (!str_starts_with($file, '/')) {
            $file = rtrim($root, '/') . '/' . ltrim($file, '/');
        }
        if ($file === '' || $root === '') return false;
        if (!is_file($file)) return false;

        $fileReal = realpath($file);
        $rootReal = realpath($root);

        if ($fileReal === false || $rootReal === false) return false;

        $rootReal = rtrim($rootReal, '/');

        return str_starts_with($fileReal, $rootReal . '/')
            && !str_contains($fileReal, '/vendor/')
            && !str_contains($fileReal, '/.git/');
    }
}
