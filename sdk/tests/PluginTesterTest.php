<?php
declare(strict_types=1);

namespace Beaver\Sdk\Tests;

use Beaver\Sdk\Testing\PluginTester;
use PHPUnit\Framework\TestCase;

final class PluginTesterTest extends TestCase
{
    public function testForSemManifestLancaExcecao(): void
    {
        $this->expectException(\RuntimeException::class);

        PluginTester::for(
            'Beaver\\Plugins\\Inexistente\\Plugin',
            '/tmp/plugin-que-nao-existe'
        );
    }
}
