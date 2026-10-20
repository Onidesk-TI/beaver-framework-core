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
// beaver-framework/src/Certify/Certificate.php

namespace Beaver\Certify;

class Certificate
{
    public function __construct(
        private string $domain,
        private string $crtPath,
        private string $keyPath
    ) {}

    public function domain(): string { return $this->domain; }
    public function crtPath(): string { return $this->crtPath; }
    public function keyPath(): string { return $this->keyPath; }

    public function expiresAt(): \DateTimeImmutable
    {
        $parsed = openssl_x509_parse(file_get_contents($this->crtPath));
        return (new \DateTimeImmutable())->setTimestamp($parsed['validTo_time_t']);
    }

    public function isValid(): bool
    {
        return $this->expiresAt() > new \DateTimeImmutable();
    }

    public function daysRemaining(): int
    {
        return (int) floor(($this->expiresAt()->getTimestamp() - time()) / 86400);
    }
}
