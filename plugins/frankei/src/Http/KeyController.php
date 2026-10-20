<?php

declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Http;

use App\Services\SiteDB;
use App\Services\UserService;
use Beaver\Http\Response;

/**
 * Frankei — Controller de chaves únicas
 *
 * Endpoints:
 *   POST /api/frankei/keys/generate    → gera chave para email + envia
 *   POST /api/frankei/keys/validate    → valida chave (login)
 *   GET  /api/frankei/keys/list        → lista chaves (admin)
 *   POST /api/frankei/keys/revoke      → revoga chave
 */
class KeyController
{
    /** Chave expira após 24h */
    private const KEY_TTL = 86400;

    /** Comprimento do código */
    private const KEY_LEN = 12;

    /** Máximo de tentativas de validação */
    private const MAX_TRIES = 5;

    /* ═════════════════════════════════════════════════════════
       POST /api/frankei/keys/generate
       { email, nome?, phone? } → gera chave e envia por email
       ═════════════════════════════════════════════════════════ */
    public function generate(): Response
    {
        $body = $_POST ?: json_decode(file_get_contents('php://input') ?: '[]', true);

        $email = strtolower(trim((string) ($body['email'] ?? '')));
        $nome  = trim((string) ($body['nome'] ?? 'Tester'));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Response::json(['ok' => false, 'erro' => 'Email inválido.'], 422);
        }

        // ── Verificar se já existe chave ativa ──
        $existing = $this->getActiveKey($email);
        if ($existing !== null) {
            return Response::json([
                'ok'      => true,
                'key'     => $existing['key_code'],
                'expira'  => $existing['expira_em'],
                'mensagem' => 'Chave ativa já existia.',
            ]);
        }

        // ── Gerar chave única ──
        $key = $this->gerarChaveUnica();
        $now = time();

        try {
            SiteDB::insert(
                'INSERT INTO trial_keys (email, key_code, criado_em, expira_em)
                 VALUES (:e, :k, :c, :x)',
                [
                    ':e' => $email,
                    ':k' => $key,
                    ':c' => $now,
                    ':x' => $now + self::KEY_TTL,
                ]
            );
        } catch (\Throwable $e) {
            return Response::json(['ok' => false, 'erro' => 'Erro ao gerar: ' . $e->getMessage()], 500);
        }

        // ── Enviar email ──
        $enviado = false;
        try {
            $setup = new SetupController();
            $reflection = new \ReflectionClass($setup);
            $method = $reflection->getMethod('enviarEmailPassword');
            $method->setAccessible(true);

            $enviado = (bool) $method->invoke(
                $setup,
                $email,
                $nome,
                $key,
                [
                    'host' => $_ENV['MAIL_HOST'] ?? getenv('MAIL_HOST') ?: '',
                    'port' => (int) ($_ENV['MAIL_PORT'] ?? getenv('MAIL_PORT') ?: 587),
                    'user' => $_ENV['MAIL_USER'] ?? getenv('MAIL_USER') ?: '',
                    'pass' => $_ENV['MAIL_PASS'] ?? getenv('MAIL_PASS') ?: '',
                    'from' => $_ENV['MAIL_FROM'] ?? getenv('MAIL_FROM') ?: '',
                    'name' => $_ENV['MAIL_FROM_NAME'] ?? 'Frankei',
                    'enc'  => $_ENV['MAIL_ENCRYPTION'] ?? getenv('MAIL_ENCRYPTION') ?: 'tls',
                ]
            );
        } catch (\Throwable $e) {
            // silencioso
        }

        // Marcar como enviado
        SiteDB::exec(
            'UPDATE trial_keys SET enviado = :s WHERE key_code = :k',
            [':s' => $enviado ? 1 : 0, ':k' => $key]
        );

        return Response::json([
            'ok'      => true,
            'key'     => $key,
            'email'   => $email,
            'enviado' => $enviado,
            'expira'  => $now + self::KEY_TTL,
        ]);
    }

    /* ═════════════════════════════════════════════════════════
       POST /api/frankei/keys/validate
       { key } → valida e devolve dados do trial
       ═════════════════════════════════════════════════════════ */
    public function validate(): Response
    {
        $body = $_POST ?: json_decode(file_get_contents('php://input') ?: '[]', true);
        $key  = strtoupper(trim((string) ($body['key'] ?? '')));

        if ($key === '') {
            return Response::json(['ok' => false, 'erro' => 'Chave vazia.'], 422);
        }

        $row = $this->getKey($key);
        if ($row === null) {
            return Response::json(['ok' => false, 'erro' => 'Chave inválida.'], 404);
        }

        // Contar tentativas
        SiteDB::exec(
            'UPDATE trial_keys SET tentativas = tentativas + 1 WHERE key_code = :k',
            [':k' => $key]
        );

        if ($row['tentativas'] >= self::MAX_TRIES) {
            return Response::json(['ok' => false, 'erro' => 'Muitas tentativas. Pede nova chave.'], 429);
        }

        if ($row['usado_em'] !== null) {
            return Response::json(['ok' => false, 'erro' => 'Chave já usada.'], 410);
        }

        if ($row['expira_em'] < time()) {
            return Response::json(['ok' => false, 'erro' => 'Chave expirada.'], 410);
        }

        // Marcar como usada
        SiteDB::exec(
            'UPDATE trial_keys SET usado_em = :t, usado_ip = :ip WHERE key_code = :k',
            [
                ':t'  => time(),
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                ':k'  => $key,
            ]
        );

        return Response::json([
            'ok'    => true,
            'email' => $row['email'],
            'expira' => $row['expira_em'],
        ]);
    }

    /* ═════════════════════════════════════════════════════════
       GET /api/frankei/keys/list  (admin)
       ═════════════════════════════════════════════════════════ */
    public function listAll(): Response
    {
        if (!UserService::autenticado()) {
            return Response::json(['ok' => false, 'erro' => 'Não autenticado.'], 401);
        }

        $rows = SiteDB::select(
            'SELECT id, email, key_code, criado_em, expira_em, usado_em, enviado, tentativas
             FROM trial_keys ORDER BY criado_em DESC LIMIT 200'
        ) ?: [];

        return Response::json(['ok' => true, 'total' => count($rows), 'keys' => $rows]);
    }

    /* ═════════════════════════════════════════════════════════
       POST /api/frankei/keys/revoke  (admin)
       { key } → invalida chave
       ═════════════════════════════════════════════════════════ */
    public function revoke(): Response
    {
        if (!UserService::autenticado()) {
            return Response::json(['ok' => false, 'erro' => 'Não autenticado.'], 401);
        }

        $body = $_POST ?: json_decode(file_get_contents('php://input') ?: '[]', true);
        $key  = strtoupper(trim((string) ($body['key'] ?? '')));

        if ($key === '') {
            return Response::json(['ok' => false, 'erro' => 'Chave vazia.'], 422);
        }

        SiteDB::exec(
            'UPDATE trial_keys SET usado_em = :t WHERE key_code = :k',
            [':t' => time(), ':k' => $key]
        );

        return Response::json(['ok' => true, 'revogada' => $key]);
    }

    /* ═════════════════════════════════════════════════════════
       HELPERS
       ═════════════════════════════════════════════════════════ */

    /**
     * Gera uma chave no formato XXXX-XXXX-XXXX (12 chars).
     * Usa alfabeto sem caracteres ambíguos (0/O, 1/I/l).
     */
    private function gerarChaveUnica(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        for ($tentativa = 0; $tentativa < 20; $tentativa++) {
            $key = '';
            for ($i = 0; $i < self::KEY_LEN; $i++) {
                if ($i > 0 && $i % 4 === 0) $key .= '-';
                $key .= $chars[random_int(0, strlen($chars) - 1)];
            }

            if ($this->getKey($key) === null) {
                return $key;
            }
        }

        throw new \RuntimeException('Não foi possível gerar chave única.');
    }

    private function getKey(string $code): ?array
    {
        $rows = SiteDB::select(
            'SELECT * FROM trial_keys WHERE key_code = :k LIMIT 1',
            [':k' => $code]
        );
        return $rows[0] ?? null;
    }

    private function getActiveKey(string $email): ?array
    {
        $rows = SiteDB::select(
            'SELECT * FROM trial_keys
             WHERE email = :e AND usado_em IS NULL AND expira_em > :n
             ORDER BY criado_em DESC LIMIT 1',
            [':e' => $email, ':n' => time()]
        );
        return $rows[0] ?? null;
    }
}
