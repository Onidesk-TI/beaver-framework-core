<?php

declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Http;

use App\Services\SiteDB;
use Beaver\Http\Response;

/**
 * Frankei — Trial temporário (30 min, sem conta)
 *
 * Rotas:
 *   POST /api/frankei/trial/start    — valida email + cria trial + envia código
 *   POST /api/frankei/trial/verify   — valida código → cookie de trial
 *   GET  /api/frankei/trial/status   — devolve estado do trial atual
 */
class TrialController
{
    public const COOKIE        = 'frankei_trial';
    public const TRIAL_MINUTES = 30;

    /* ═════════════════════════════════════════════════════════
       POST /api/frankei/trial/start
       { email, nome, phone, path } → envia código
       ═════════════════════════════════════════════════════════ */
    public function start(): Response
    {
        $body  = $_POST ?: json_decode(file_get_contents('php://input') ?: '[]', true);
        $email = strtolower(trim((string) ($body['email'] ?? '')));
        $nome  = trim((string) ($body['nome'] ?? 'Tester'));
        $phone = preg_replace('/[^0-9+]/', '', (string) ($body['phone'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Response::json(['ok' => false, 'erro' => 'Email inválido.'], 422);
        }

        $fp = $this->fingerprint();

        // ── Já houve trial deste fingerprint? ──
        $rows = SiteDB::select(
            'SELECT * FROM trial_sessions WHERE fingerprint = :fp ORDER BY id DESC LIMIT 1',
            [':fp' => $fp]
        );
        $last = $rows[0] ?? null;

        if ($last) {
            // Já usou trial — não pode voltar
            return Response::json([
                'ok'     => false,
                'erro'   => 'Já usaste o trial gratuito neste dispositivo. Cria uma conta para continuar.',
                'reason' => 'already_used',
            ], 403);
        }

        // ── Gerar código de verificação (6 dígitos) ──
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $now  = time();

        // Guardar token temporário com código (não usa a tabela users)
        $token = bin2hex(random_bytes(32));
        $expires = $now + (15 * 60); // 15 min para usar o código

        // Usamos trial_sessions para guardar o código pendente
        // (mesma tabela, expires_at serve para o código)
        SiteDB::insert(
            'INSERT INTO trial_sessions (token, email, nome, phone, fingerprint, started_at, expires_at, criado_em)
             VALUES (:t, :e, :n, :ph, :fp, :s, :x, :c)',
            [
                ':t'  => $token,
                ':e'  => $email,
                ':n'  => $nome,
                ':ph' => $phone,
                ':fp' => $fp,
                ':s'  => $now,
                ':x'  => $expires,
                ':c'  => $now,
            ]
        );

        // Guardar o código em memória no próprio token (base64 do código + token)
        // O código fica associado ao token via hash
        $codeHash = password_hash($code, PASSWORD_BCRYPT);
        SiteDB::exec(
            'UPDATE trial_sessions SET nome = :n WHERE token = :t',
            [':n' => $codeHash . '|' . $nome, ':t' => $token]
        );

        // Enviar email com o código
        $mailOk  = false;
        $mailErr = '';
        try {
            $setup    = new SetupController();
            $ref      = new \ReflectionClass($setup);
            $method   = $ref->getMethod('enviarEmailPassword');
            $method->setAccessible(true);

            $mailOk = (bool) $method->invoke(
                $setup,
                $email,
                $nome,
                $code,
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
            $mailErr = $e->getMessage();
        }

        $resp = [
            'ok'      => true,
            'token'   => $token,
            'email'   => $email,
            'enviado' => $mailOk,
            'expira'  => $expires,
        ];
        if (!$mailOk) {
            $resp['code']     = $code;
            $resp['mail_erro'] = $mailErr;
        }

        return Response::json($resp);
    }

    /* ═════════════════════════════════════════════════════════
       POST /api/frankei/trial/verify
       { token, code } → cria sessão de trial via cookie
       ═════════════════════════════════════════════════════════ */
    public function verify(): Response
    {
        $body  = $_POST ?: json_decode(file_get_contents('php://input') ?: '[]', true);
        $token = trim((string) ($body['token'] ?? ''));
        $code  = trim((string) ($body['code'] ?? ''));

        if ($token === '' || $code === '') {
            return Response::json(['ok' => false, 'erro' => 'Dados em falta.'], 422);
        }

        $rows = SiteDB::select('SELECT * FROM trial_sessions WHERE token = :t LIMIT 1', [':t' => $token]);
        $row  = $rows[0] ?? null;

        if (!$row) {
            return Response::json(['ok' => false, 'erro' => 'Token inválido.'], 404);
        }

        if ((int) $row['expires_at'] < time()) {
            return Response::json(['ok' => false, 'erro' => 'Código expirado.'], 410);
        }

        // Extrair hash + nome
        $parts = explode('|', (string) $row['nome'], 2);
        $codeHash = $parts[0] ?? '';
        $nome     = $parts[1] ?? 'Tester';

        if (!password_verify($code, $codeHash)) {
            return Response::json(['ok' => false, 'erro' => 'Código incorreto.'], 403);
        }

        // Renovar o trial: 30 min a partir de agora
        $now     = time();
        $expires = $now + (self::TRIAL_MINUTES * 60);

        SiteDB::exec(
            'UPDATE trial_sessions SET started_at = :s, expires_at = :x, nome = :n WHERE token = :t',
            [':s' => $now, ':x' => $expires, ':n' => $nome, ':t' => $token]
        );

        // Cookie com o token
        $this->setCookie($token, $expires);

        return Response::json([
            'ok'       => true,
            'expires'  => $expires,
            'redirect' => '/frankei',
            'mensagem' => 'Trial ativo. Tens 30 minutos.',
        ]);
    }

    /* ═════════════════════════════════════════════════════════
       GET /api/frankei/trial/status
       ═════════════════════════════════════════════════════════ */
    public function status(): Response
    {
        $trial = self::current();
        if (!$trial) {
            return Response::json(['ok' => false, 'ativo' => false]);
        }
        return Response::json([
            'ok'      => true,
            'ativo'   => true,
            'email'   => $trial['email'],
            'expires' => (int) $trial['expires_at'],
            'seconds' => max(0, (int) $trial['expires_at'] - time()),
        ]);
    }

    /* ═════════════════════════════════════════════════════════
       HELPERS
       ═════════════════════════════════════════════════════════ */

    /**
     * Devolve o trial ativo atual (ou null).
     * Valida: cookie + token + fingerprint + expiração.
     */
    public static function current(): ?array
    {
        $token = $_COOKIE[self::COOKIE] ?? '';
        if ($token === '') return null;

        $rows = SiteDB::select(
            'SELECT * FROM trial_sessions WHERE token = :t LIMIT 1',
            [':t' => $token]
        );
        $row = $rows[0] ?? null;

        if (!$row) return null;
        if ((int) $row['expires_at'] < time()) return null;
        if ((string) $row['fingerprint'] !== self::fingerprint()) return null;

        return $row;
    }

    /**
     * Já houve trial deste fingerprint?
     */
    public static function alreadyUsed(): bool
    {
        $rows = SiteDB::select(
            'SELECT id FROM trial_sessions WHERE fingerprint = :fp AND expires_at < :n LIMIT 1',
            [':fp' => self::fingerprint(), ':n' => time()]
        );
        return !empty($rows);
    }

    /**
     * Fingerprint do dispositivo (User-Agent + Accept-Language + prefixo IP).
     */
    public static function fingerprint(): string
    {
        $ua   = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $lang = (string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
        $ip   = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        // Prefixo /24 do IPv4 (ignora o último octeto)
        if (preg_match('/^(\d+\.\d+\.\d+)\./', $ip, $m)) {
            $ip = $m[1] . '.0';
        }

        return hash('sha256', $ua . '|' . $lang . '|' . $ip);
    }

    private function setCookie(string $token, int $expires): void
    {
        if (headers_sent()) return;
        setcookie(self::COOKIE, $token, [
            'expires'  => $expires,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']),
        ]);
    }
}
