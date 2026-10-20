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

namespace Beaver\Sdk\Testing;

use Beaver\Plugin\PluginBase;
use Beaver\Sdk\Manifest;
use Beaver\Sdk\ManifestValidator;

final class PluginTester
{
    public FakeHooks $hooks;
    public array $validationErrors = [];
    public ?PluginBase $instance = null;

    private function __construct(
        private string $pluginClass,
        private string $pluginPath,
        private array  $manifestData,
    ) {
        $this->hooks = new FakeHooks();
    }

    public static function for(string $pluginClass, string $pluginPath): self
    {
        $pluginPath   = rtrim($pluginPath, '/');
        $manifestFile = $pluginPath . '/plugin.json';

        if (!is_file($manifestFile)) {
            throw new \RuntimeException("plugin.json não encontrado em $pluginPath");
        }

        $data = json_decode((string) file_get_contents($manifestFile), true);
        if (!is_array($data)) {
            throw new \RuntimeException("plugin.json inválido (JSON malformado)");
        }

        return new self($pluginClass, $pluginPath, $data);
    }

    public function validateManifest(): self
    {
        $this->validationErrors = ManifestValidator::validate($this->manifestData);
        return $this;
    }

    public function manifest(): Manifest
    {
        return Manifest::fromArray($this->manifestData);
    }

    public function boot(): self
    {
        $main = $this->pluginPath . '/' . ($this->manifestData['main'] ?? 'Plugin.php');

        if (is_file($main) && !class_exists($this->pluginClass)) {
            require_once $main;
        }

        if (!class_exists($this->pluginClass)) {
            throw new \RuntimeException("Classe não encontrada: {$this->pluginClass}");
        }

        $this->instance = new $this->pluginClass($this->pluginPath, $this->manifestData);
        $this->hooks->emit('plugin.booted', $this->instance);
        $this->instance->boot();

        return $this;
    }
}
