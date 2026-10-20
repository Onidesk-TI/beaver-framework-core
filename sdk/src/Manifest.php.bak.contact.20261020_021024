<?php

declare(strict_types=1);

/**
 * Beaver Framework — Modern PHP framework with a plugin ecosystem.
 *
 * @package    Beaver SDK
 * @version    0.1.0
 * @author     Franco <onidesk@outlook.com>
 * @copyright  2026 Onidesk
 * @license    MIT <https://opensource.org/licenses/MIT>
 * @link       https://github.com/Onidesk-TI/beaver-framework
 */

namespace Beaver\Sdk;

final class Manifest
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly string $version,
        public readonly string $namespace,
        public readonly string $main,
        public readonly string $beaverVersion,
        public readonly string $apiVersion,
        public readonly string $author,
        public readonly string $description,
        public readonly array  $permissions,
        public readonly array  $requires,
        public readonly array  $adminMenu,
        public readonly ?string $routes,
        public readonly array  $raw,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name:          $data['name']           ?? '',
            slug:          $data['slug']           ?? '',
            version:       $data['version']        ?? '0.0.0',
            namespace:     $data['namespace']      ?? '',
            main:          $data['main']           ?? '',
            beaverVersion: $data['beaver_version'] ?? '>=0.1.0',
            apiVersion:    $data['api_version']    ?? SdkVersion::API_VERSION,
            author:        $data['author']         ?? '',
            description:   $data['description']    ?? '',
            permissions:   $data['permissions']    ?? [],
            requires:      $data['requires']       ?? [],
            adminMenu:     $data['admin_menu']     ?? [],
            routes:        $data['routes']         ?? null,
            raw:           $data,
        );
    }

    public function className(): string
    {
        return $this->namespace . '\\' . basename($this->main, '.php');
    }

    public function hasPermission(string $scope): bool
    {
        return in_array($scope, $this->permissions, true);
    }
}
