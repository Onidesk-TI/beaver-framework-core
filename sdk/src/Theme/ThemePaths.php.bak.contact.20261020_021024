<?php

/**
 * Beaver Framework — ThemePaths
 *
 * Resolve os caminhos onde os temas vivem.
 * Segue o padrão dos plugins: mode controla a ordem de pesquisa.
 *
 * @package    Beaver\Sdk\Theme
 * @version    0.1.0
 * @author     Franco <onidesk@outlook.com>
 * @copyright  2026 Onidesk
 * @license    MIT <https://opensource.org/licenses/MIT>
 */

declare(strict_types=1);

namespace Beaver\Sdk\Theme;

final class ThemePaths
{
    /** @var string[] */
    private array $searchPaths;

    private string $skeletonPath;
    private string $storageFile;

    /**
     * @param string[] $searchPaths ordem de prioridade (primeiro ganha)
     */
    public function __construct(
        array $searchPaths,
        string $skeletonPath,
        string $storageFile,
    ) {
        $this->searchPaths  = array_map(fn($p) => rtrim($p, '/'), $searchPaths);
        $this->skeletonPath = rtrim($skeletonPath, '/');
        $this->storageFile  = $storageFile;
    }

    public static function fromConfig(array $config): self
    {
        $mode = $config['mode'] ?? 'prod';

        // Constrói a lista por ordem de prioridade conforme o mode
        $paths = match ($mode) {
            'dev'     => [
                $config['dev_path']     ?? null,
                $config['staging_path'] ?? null,
                $config['path']         ?? null,
                $config['internal_path'] ?? null,
            ],
            'staging' => [
                $config['staging_path'] ?? null,
                $config['path']         ?? null,
                $config['internal_path'] ?? null,
            ],
            default   => [ // prod
                $config['path']         ?? null,
                $config['internal_path'] ?? null,
            ],
        };

        // Remove nulos e vazios
        $paths = array_values(array_filter($paths, fn($p) => is_string($p) && $p !== ''));

        return new self(
            searchPaths:  $paths,
            skeletonPath: $config['skeleton_path'] ?? '',
            storageFile:  $config['storage']       ?? '',
        );
    }

    /** @return string[] */
    public function searchPaths(): array
    {
        return $this->searchPaths;
    }

    public function skeleton(): string
    {
        return $this->skeletonPath;
    }

    public function storage(): string
    {
        return $this->storageFile;
    }
}
