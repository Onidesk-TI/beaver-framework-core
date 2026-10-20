<?php

declare(strict_types=1);

namespace Beaver\Plugins\FrankeiTrial\Http;

use Beaver\Http\Request;
use Beaver\Http\Response;
use Beaver\Plugins\FrankeiTrial\Support\TrialLimits;

/**
 * Report Beaver — versão trial.
 * Reutiliza o BeaverReportGenerator do plugin Frankei.
 * Grava no MESMO storage (storage/frankei-reports/{userId}/current/),
 * por isso o ficheiro fica acessível aos dois plugins.
 */
class TrialReportController
{
    /**
     * POST /api/frankei-trial/report/beaver
     * Gera o report e devolve a URL.
     */
    public function generate(Request $req): Response
    {
        if (!$this->autenticado()) {
            return Response::json(['ok' => false, 'error' => 'Não autenticado'], 401);
        }

        // Carregar BeaverReportGenerator do frankei (sem composer)
        $root = $this->projectRoot();
        $file = $root . '/plugins/frankei/src/Frankei/Terminal/BeaverReportGenerator.php';
        if (is_file($file)) {
            require_once $file;
        }

        $class = '\\Beaver\\Plugins\\Frankei\\Frankei\\Terminal\\BeaverReportGenerator';
        if (!class_exists($class)) {
            return Response::json([
                'ok'    => false,
                'error' => 'BeaverReportGenerator indisponível. Verifica se o plugin Frankei está ativo.',
            ], 503);
        }

        try {
            $userId = $this->userId();
            $plan   = TrialLimits::plan();

            $gen    = new $class();
            $result = $gen->generate($userId, $plan);

            if (empty($result['ok'])) {
                return Response::json([
                    'ok'    => false,
                    'error' => $result['error'] ?? 'Falha ao gerar report',
                ], 422);
            }

            return Response::json([
                'ok'   => true,
                'plan' => $plan,
                'url'  => '/api/frankei-trial/report/beaver/view',
                'path' => $result['path'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return Response::json([
                'ok'    => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/frankei-trial/report/beaver/view
     * Serve o HTML gerado.
     */
    public function view(Request $req): Response
    {
        if (!$this->autenticado()) {
            return Response::json(['ok' => false, 'error' => 'Não autenticado'], 401);
        }

        $userId = $this->userId();
        $root   = $this->projectRoot();
        $file   = $root . '/storage/frankei-reports/' . $userId . '/current/beaver-test-report.html';

        if (!is_file($file)) {
            return Response::text('Report ainda não foi gerado. Clica em 📊 Report primeiro.', 404);
        }

        return (new Response(file_get_contents($file)))
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    private function autenticado(): bool
    {
        try { return \App\Services\UserService::autenticado(); }
        catch (\Throwable $e) { return false; }
    }

    private function userId(): int
    {
        try {
            return (int) (\App\Services\UserService::id() ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function projectRoot(): string
    {
        try {
            return \Beaver\Foundation\Application::getInstance()->basePath ?? getcwd();
        } catch (\Throwable $e) {
            return getcwd();
        }
    }
}
