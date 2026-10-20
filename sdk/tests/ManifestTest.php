<?php
declare(strict_types=1);

namespace Beaver\Sdk\Tests;

use Beaver\Sdk\Manifest;
use PHPUnit\Framework\TestCase;

final class ManifestTest extends TestCase
{
    public function testFromArrayLeCampos(): void
    {
        $m = Manifest::fromArray([
            'name'           => 'Meu Plugin',
            'slug'           => 'meu-plugin',
            'version'        => '1.2.3',
            'namespace'      => 'Beaver\\Plugins\\MeuPlugin',
            'main'           => 'Plugin.php',
            'beaver_version' => '>=0.1.0',
        ]);

        $this->assertSame('Beaver\\Plugins\\MeuPlugin\\Plugin', $m->className());
    }

    public function testHasPermission(): void
    {
        $m = Manifest::fromArray([
            'name'           => 'Meu Plugin',
            'slug'           => 'meu-plugin',
            'version'        => '1.0.0',
            'namespace'      => 'Beaver\\Plugins\\MeuPlugin',
            'main'           => 'Plugin.php',
            'beaver_version' => '>=0.1.0',
            'permissions'    => ['db.read'],
        ]);

        $this->assertTrue($m->hasPermission('db.read'));
        $this->assertFalse($m->hasPermission('network.outbound'));
    }
}
