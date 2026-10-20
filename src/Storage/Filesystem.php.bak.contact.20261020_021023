<?php

/**
 * Beaver Framework — Modern PHP framework with a plugin ecosystem.
 *
 * @package    Beaver Framework
 * @author     Franco <onidesk@outlook.com>
 * @copyright  2026 Onidesk
 * @license    MIT <https://opensource.org/licenses/MIT>
 * @link       https://github.com/Onidesk-TI/beaver-framework
 *
 * ──────────────────────────────────────────────────────────────
 * API HÍBRIDA — três formas de usar
 * ──────────────────────────────────────────────────────────────
 *
 *   1) Helper global (uso comum)
 *         storage_put('logs/a.txt', 'conteúdo');
 *         storage_get('logs/a.txt');
 *         storage_disk('public')->put('fotos/x.jpg', $bin);
 *
 *   2) Container (controlo)
 *         $fs = app()->make(Filesystem::class);
 *         $fs->disk('public')->put('fotos/x.jpg', $bin);
 *
 *   3) Injeção de dependências
 *         public function __construct(private Filesystem $fs) {}
 */

namespace Beaver\Storage;

use Beaver\Storage\Contracts\DiskDriver;

class Filesystem
{
    /** @var array<string, DiskDriver> */
    private array $disks = [];

    /** Nome do disco default. */
    private string $default;

    /**
     * @param array<string, DiskDriver> $disks
     */
    public function __construct(array $disks = [], string $default = 'local')
    {
        $this->disks   = $disks;
        $this->default = $default;
    }

    /**
     * Troca ou adiciona um disco.
     */
    public function setDisk(string $name, DiskDriver $driver): void
    {
        $this->disks[$name] = $driver;
    }

    /**
     * Devolve um disco pelo nome (ou o default).
     */
    public function disk(?string $name = null): DiskDriver
    {
        $name ??= $this->default;

        if (!isset($this->disks[$name])) {
            throw new \RuntimeException("Disk not configured: {$name}");
        }

        return $this->disks[$name];
    }

    /**
     * Delega para o disco default.
     */
    public function __call(string $method, array $args): mixed
    {
        return $this->disk()->{$method}(...$args);
    }

    /**
     * Nome do disco default.
     */
    public function defaultDisk(): string
    {
        return $this->default;
    }
}
