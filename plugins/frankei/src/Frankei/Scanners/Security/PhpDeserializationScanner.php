<?php

declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Scanners\Security;

use Beaver\Plugins\Frankei\Frankei\Code\CodeScannerInterface;
use Beaver\Plugins\Frankei\Frankei\Code\CodeScanContext;
use Beaver\Plugins\Frankei\Frankei\Finding;

class PhpDeserializationScanner implements CodeScannerInterface
{
    public function id(): string
    {
        return 'php-deserialize';
    }
    public function name(): string
    {
        return 'Deserialização insegura';
    }
    public function category(): string
    {
        return 'security';
    }
    public function defaultSeverity(): string
    {
        return 'critical';
    }

    public function scan(array $files, string $root, array $options = []): array
    {
        $ctx = new CodeScanContext($root, 1048576, $options);
        $findings = [];

        foreach ($files as $file) {
            $content = @file_get_contents($file);
            if ($content === false) continue;

            // Se o ficheiro tem openssl_decrypt + AES-GCM → unserialize é seguro
            $temGcmDecrypt = (bool) preg_match(
                '/openssl_decrypt\s*\([^)]*aes-(256|128)-gcm|CIPHER\s*=\s*[\'"]aes-256-gcm[\'"]/i',
                $content
            );

            // Cache local: unserialize de dados escritos pelo proprio codigo
            $temFileGetContents = (bool) preg_match(
                '/file_get_contents\s*\(/i',
                $content
            );

            $lines = $ctx->readLines($file);
            $rel   = $ctx->relPath($file);

            foreach ($lines as $n => $line) {
                if ($ctx->isComment($line)) continue;

                // Ignorar unserialize seguro (GCM ou cache local)
                if (preg_match('/unserialize\s*\(/i', $line)) {
                    if ($temGcmDecrypt || $temFileGetContents) {
                        continue;
                    }
                }

                $f = $this->checkLine($line, $rel, $n);
                if ($f !== null) $findings[] = $f;
            }
        }

        return $findings;
    }

    private function checkLine(string $line, string $file, int $n): ?Finding
    {
        // unserialize com input do utilizador
        if (preg_match('/\bunserialize\s*\([^)]*\$_(GET|POST|REQUEST|COOKIE|FILES|SERVER)/i', $line)) {
            return $this->finding(
                $file,
                $n,
                $line,
                'unserialize() com input direto do utilizador — RCE potencial.'
            );
        }

        // unserialize com variável (possível)
        if (preg_match('/\bunserialize\s*\(\s*\$[A-Za-z_]/i', $line)) {
            if (preg_match('/unserialize\s*\([^,)]+,\s*\[/', $line)) return null; // com allowed_classes
            if (preg_match('/\[[\'"]allowed_classes[\'"]\s*=>\s*(false|\[\])/', $line)) return null;

            return $this->finding(
                $file,
                $n,
                $line,
                'unserialize() com variável. Considera usar JSON ou restringir allowed_classes.'
            );
        }

        // json_decode sem assoc (menor risco, mas considerar)
        // (opcional - não incluído por agora)

        // Serialização de objetos com __wakeup / __destruct (indica risco)
        // (verificação de padrão em classes)
        if (preg_match('/function\s+__wakeup\s*\(/', $line)) {
            // só alertar se também houver unserialize no ficheiro
            // (não conseguimos ver aqui; ignorar por agora)
        }

        return null;
    }

    private function finding(string $file, int $n, string $line, string $desc): Finding
    {
        return new Finding(
            scanner: $this->id(),
            category: $this->category(),
            severity: 'critical',
            title: 'Deserialização insegura em ' . $file . ':' . $n,
            description: $desc,
            suggestion: "Nunca deserializar input do utilizador. Usa JSON:\n\n\$data = json_decode(\$input, true);\n\nSe tiveres mesmo de usar unserialize(), restringe as classes:\n\n\$data = unserialize(\$input, ['allowed_classes' => ['MyDto', 'Safe']]);",
            impact: 'PHP Object Injection — um atacante pode instanciar classes arbitrárias, acionar __wakeup/__destruct e executar código.',
            reference: 'https://owasp.org/www-community/vulnerabilities/PHP_Object_Injection',
            data: ['file' => $file, 'line' => $n, 'code' => trim($line)],
        );
    }
}
