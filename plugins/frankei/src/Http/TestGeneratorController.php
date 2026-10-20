<?php

declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Http;

use Beaver\Foundation\Application;
use Beaver\Http\Request;
use Beaver\Http\Response;
use Beaver\Auth\Auth;
use Beaver\Plugins\Frankei\Frankei\Tests\TestSkeletonGenerator;

class TestGeneratorController
{
    public function preview(Request $request): Response
    {
        if (!$this->authorize()) return Response::json(['error' => 'Não autenticado'], 401);

        $b      = $request->body ?? [];
        $class  = trim((string) ($b['class']  ?? ''));
        $file   = trim((string) ($b['file']   ?? ''));
        $target = (string) ($b['target'] ?? 'Unit');
        $engine = (string) ($b['engine'] ?? 'pest');

        $gen = new TestSkeletonGenerator($this->root());

        // Fallback: se só temos file, derivar FQCN
        if ($class === '' || strpos($class, '\\') === false) {
            if ($file !== '' && is_file($file)) {
                $derived = $gen->deriveFqcnFromPath($file);
                if ($derived) $class = $derived;
            }
        }

        if ($class === '') {
            return Response::json(['error' => 'Não consegui resolver a classe (falta class ou file).'], 422);
        }

        try {
            $out = $gen->generate($class, $target, $engine);
        } catch (\Throwable $e) {
            return Response::json(['error' => $e->getMessage(), 'class' => $class, 'file' => $file], 400);
        }

        return Response::json([
            'ok'          => true,
            'path'        => $out['path'],
            'testClass'   => $out['testClass'],
            'filterName'  => $out['filterName'],
            'code'        => $out['code'],
            'exists'      => $out['exists'],
            'methodCount' => $out['methodCount'],
            'httpBound'   => $out['httpBound'] ?? false,
        ]);
    }

    public function create(Request $request): Response
    {
        if (!$this->authorize()) return Response::json(['error' => 'Não autenticado'], 401);

        $b         = $request->body ?? [];
        $class     = trim((string) ($b['class']  ?? ''));
        $file      = trim((string) ($b['file']   ?? ''));
        $target    = (string) ($b['target'] ?? 'Unit');
        $engine    = (string) ($b['engine'] ?? 'pest');
        $code      = (string) ($b['code']   ?? '');
        $overwrite = (bool)   ($b['overwrite'] ?? false);

        if (trim($code) === '') return Response::json(['error' => 'code vazio'], 422);

        $gen = new TestSkeletonGenerator($this->root());

        // Fallback: se só temos file, derivar FQCN
        if ($class === '' || strpos($class, '\\') === false) {
            if ($file !== '' && is_file($file)) {
                $derived = $gen->deriveFqcnFromPath($file);
                if ($derived) $class = $derived;
            }
        }

        if ($class === '') {
            return Response::json(['error' => 'Não consegui resolver a classe (falta class ou file).'], 422);
        }

        try {
            $out = $gen->generate($class, $target, $engine);
        } catch (\Throwable $e) {
            return Response::json(['error' => $e->getMessage(), 'class' => $class, 'file' => $file], 400);
        }

        $path = $out['absolute'];
        $dir  = dirname($path);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return Response::json(['error' => 'Falha ao criar pasta: ' . $dir], 500);
        }

        // Validação de sintaxe antes de gravar — evita bloquear todo o Pest
        $tmp = tempnam(sys_get_temp_dir(), 'fk-test-');
        if ($tmp === false || @file_put_contents($tmp, $code) === false) {
            return Response::json(['error' => 'Falha ao preparar ficheiro temporário para validação'], 500);
        }

        $lintOut = [];
        $lintCode = 0;
        @exec('php -l ' . escapeshellarg($tmp) . ' 2>&1', $lintOut, $lintCode);
        @unlink($tmp);

        if ($lintCode !== 0) {
            $msg = implode('\n', $lintOut);
            $msg = preg_replace('/ in ' . preg_quote(sys_get_temp_dir(), '/') . '[^ ]+ on line /', ' on line ', $msg);
            return Response::json([
                'error' => 'Erro de sintaxe no ficheiro gerado — não gravado.',
                'lint'  => trim($msg),
            ], 422);
        }

        $exists = is_file($path);
        if ($exists && !$overwrite) {
            return Response::json([
                'error'  => 'Já existe',
                'path'   => $out['path'],
                'exists' => true,
            ], 409);
        }

        if ($exists) {
            @copy($path, $path . '.bak.' . date('Ymd_His'));
        }

        if (@file_put_contents($path, $code) === false) {
            return Response::json(['error' => 'Falha ao gravar'], 500);
        }

        return Response::json([
            'ok'         => true,
            'path'       => $out['path'],
            'testClass'  => $out['testClass'],
            'filterName' => $out['filterName'],
            'bytes'      => strlen($code),
        ]);
    }

    private function authorize(): bool
    {
        $env = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'production';
        return $env !== 'production' || Auth::check();
    }

    private function root(): string
    {
        return Application::getInstance()->basePath ?? getcwd();
    }

    /**
     * POST /api/frankei/tests/prune
     * Percorre tests/, valida com php -l, move os inválidos para _trash/tests_TIMESTAMP/.
     * Não apaga nada — sempre reversível.
     */
    public function prune(Request $request): Response
    {
        if (!$this->authorize()) return Response::json(['error' => 'Não autenticado'], 401);

        $root     = $this->root();
        $testsDir = $root . '/tests';
        $trashDir = $root . '/_trash/tests_' . date('Ymd_His');

        if (!is_dir($testsDir)) {
            return Response::json(['ok' => true, 'moved' => [], 'kept' => 0, 'errors' => []]);
        }

        $moved  = [];
        $kept   = 0;
        $errors = [];

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($testsDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($it as $f) {
            if (!$f->isFile()) continue;
            if (!str_ends_with($f->getFilename(), '.php')) continue;

            $path = $f->getPathname();
            $out  = [];
            $code = 0;
            @exec('php -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);

            if ($code === 0) { $kept++; continue; }

            $rel  = ltrim(substr($path, strlen($testsDir) + 1), '/');
            $dest = $trashDir . '/' . str_replace('/', '__', $rel);

            if (!is_dir(dirname($dest))) @mkdir(dirname($dest), 0755, true);

            if (@rename($path, $dest)) {
                $moved[] = [
                    'from' => 'tests/' . $rel,
                    'to'   => '_trash/tests_' . basename($trashDir) . '/' . basename($dest),
                    'lint' => trim(implode("\n", $out)),
                ];
            } else {
                $errors[] = 'Falha ao mover: ' . $rel;
            }
        }

        return Response::json([
            'ok'     => true,
            'moved'  => $moved,
            'kept'   => $kept,
            'errors' => $errors,
        ]);
    }
}
