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
// beaver-framework/src/Certify/CertificateManager.php

namespace Beaver\Certify;

use RuntimeException;

class CertificateManager
{
    public function __construct(
        private array $config = []
    ) {}

    /**
     * Gera certificado self-signed para desenvolvimento local.
     */
    public function makeSelfSigned(
        string $domain,
        int $days = 365,
        int $keySize = 2048,
        bool $force = false
    ): Certificate {
        $storage = $this->config['paths']['storage']
            ?? throw new RuntimeException('Certify: paths.storage não configurado');

        if (!is_dir($storage) && !mkdir($storage, 0755, true)) {
            throw new RuntimeException("Não foi possível criar {$storage}");
        }

        $crtPath = "{$storage}/{$domain}.crt";
        $keyPath = "{$storage}/{$domain}.key";

        if (!$force && file_exists($crtPath)) {
            throw new RuntimeException("Certificado já existe: {$crtPath} (usa --force)");
        }

        // Gera chave privada + CSR + certificado self-signed
        $dn = [
            'countryName'            => $this->config['modes']['self-signed']['country'] ?? 'PT',
            'organizationName'       => $this->config['modes']['self-signed']['org'] ?? 'Beaver Local',
            'commonName'             => $domain,
        ];

        $privateKey = openssl_pkey_new([
            'private_key_bits' => $keySize,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($privateKey === false) {
            throw new RuntimeException('Falha ao gerar chave privada: ' . openssl_error_string());
        }

        $csr = openssl_csr_new($dn, $privateKey, ['digest_alg' => 'sha256']);
        if ($csr === false) {
            throw new RuntimeException('Falha ao gerar CSR: ' . openssl_error_string());
        }

        $cert = openssl_csr_sign($csr, null, $privateKey, $days, ['digest_alg' => 'sha256']);
        if ($cert === false) {
            throw new RuntimeException('Falha ao assinar certificado: ' . openssl_error_string());
        }

        openssl_x509_export($cert, $crtContent);
        openssl_pkey_export($privateKey, $keyContent);

        file_put_contents($crtPath, $crtContent);
        file_put_contents($keyPath, $keyContent);
        chmod($keyPath, 0600);

        return new Certificate($domain, $crtPath, $keyPath);
    }

    /**
     * Emite certificado via Let's Encrypt (ACME).
     */
    public function issue(
        string $domain,
        string $email,
        string $challenge = 'http-01'
    ): Certificate {
        // Aqui integrarias uma lib ACME como acmephp/acmephp
        // Por agora, delegamos para o certbot do sistema
        $storage = $this->config['paths']['storage'];

        $cmd = sprintf(
            'certbot certonly --non-interactive --agree-tos --email %s --%s -d %s --cert-path %s --key-path %s',
            escapeshellarg($email),
            escapeshellarg($challenge),
            escapeshellarg($domain),
            escapeshellarg("{$storage}/{$domain}.crt"),
            escapeshellarg("{$storage}/{$domain}.key")
        );

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException("certbot falhou: " . implode("\n", $output));
        }

        return new Certificate($domain, "{$storage}/{$domain}.crt", "{$storage}/{$domain}.key");
    }

    /**
     * Verifica a saúde de um certificado.
     */
    public function check(string $domain): array
    {
        $storage = $this->config['paths']['storage'];
        $crtPath = "{$storage}/{$domain}.crt";

        if (!file_exists($crtPath)) {
            return ['valid' => false, 'error' => "Certificado não encontrado: {$crtPath}"];
        }

        $parsed = openssl_x509_parse(file_get_contents($crtPath));

        if ($parsed === false) {
            return ['valid' => false, 'error' => 'Certificado inválido'];
        }

        $expiresAt = $parsed['validTo_time_t'];
        $daysRemaining = (int) floor(($expiresAt - time()) / 86400);

        return [
            'valid'          => $daysRemaining > 0,
            'domain'         => $domain,
            'issuer'         => $parsed['issuer']['CN'] ?? 'Desconhecido',
            'expires_at'     => date('Y-m-d', $expiresAt),
            'days_remaining' => $daysRemaining,
            'tls_min'        => $this->config['tls']['min_version'] ?? 'TLSv1.2',
            'hsts'           => $this->config['tls']['hsts'] ?? false,
        ];
    }

    /**
     * Renova certificados que estão perto de expirar.
     */
    public function renew(int $beforeDays = 30, bool $force = false): array
    {
        $storage = $this->config['paths']['storage'];
        $renewed = [];

        foreach (glob("{$storage}/*.crt") as $crt) {
            $domain = basename($crt, '.crt');
            $check = $this->check($domain);

            if ($force || ($check['days_remaining'] ?? 0) < $beforeDays) {
                $this->issue($domain, $this->config['modes']['letsencrypt']['email']);
                $renewed[] = $domain;
            }
        }

        return $renewed;
    }

    /**
     * Lista todos os certificados.
     */
    public function list(): array
    {
        $storage = $this->config['paths']['storage'];
        $certs = [];

        foreach (glob("{$storage}/*.crt") as $crt) {
            $domain = basename($crt, '.crt');
            $certs[] = $this->check($domain);
        }

        return $certs;
    }
}
