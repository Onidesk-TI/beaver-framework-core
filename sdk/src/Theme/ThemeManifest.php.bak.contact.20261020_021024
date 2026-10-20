<?php

declare(strict_types=1);

/**
 * Beaver Framework — ThemeManifest
 *
 * Value object imutável que representa um theme.json validado.
 *
 * Campos essenciais são expostos como propriedades tipadas (readonly).
 * Campos livres (license, date, techsUsed, tags, ...) ficam acessíveis
 * via raw[] ou via get()/has().
 *
 * @package    Beaver\Sdk\Theme
 * @version    0.1.0
 * @author     Franco <onidesk@outlook.com>
 * @copyright  2026 Onidesk
 * @license    MIT <https://opensource.org/licenses/MIT>
 */

namespace Beaver\Sdk\Theme;

final class ThemeManifest
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly string $version,
        public readonly string $beaverVersion,
        public readonly ?string $apiVersion = null,
        public readonly ?string $author = null,
        public readonly ?string $description = null,
        public readonly ?string $screenshot = null,
        public readonly ?string $parent = null,
        public readonly array $colors = [],
        public readonly array $supports = [],
        public readonly array $assets = [],
        public readonly array $raw = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name:          (string) ($data['name'] ?? ''),
            slug:          (string) ($data['slug'] ?? ''),
            version:       (string) ($data['version'] ?? ''),
            beaverVersion: (string) ($data['beaver_version'] ?? ''),
            apiVersion:    $data['api_version'] ?? null,
            author:        $data['author'] ?? null,
            description:   $data['description'] ?? null,
            screenshot:    $data['screenshot'] ?? null,
            parent:        $data['parent'] ?? null,
            colors:        (array) ($data['colors'] ?? []),
            supports:      (array) ($data['supports'] ?? []),
            assets:        (array) ($data['assets'] ?? []),
            raw:           $data,
        );
    }

    public function toArray(): array
    {
        return $this->raw;
    }

    public function hasSupport(string $feature): bool
    {
        return in_array($feature, $this->supports, true);
    }

    public function isChild(): bool
    {
        return $this->parent !== null && $this->parent !== '';
    }

    // ---------- campos livres ----------

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->raw[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->raw);
    }
}
