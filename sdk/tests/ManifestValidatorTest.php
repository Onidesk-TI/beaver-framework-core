<?php
declare(strict_types=1);

namespace Beaver\Sdk\Tests;

use Beaver\Sdk\ManifestValidator;
use PHPUnit\Framework\TestCase;

final class ManifestValidatorTest extends TestCase
{
    public function testManifestValidoNaoTemErros(): void
    {
        $erros = ManifestValidator::validate([
            'name'           => 'Meu Plugin',
            'slug'           => 'meu-plugin',
            'version'        => '1.0.0',
            'namespace'      => 'Beaver\\Plugins\\MeuPlugin',
            'main'           => 'Plugin.php',
            'beaver_version' => '>=0.1.0',
        ]);

        $this->assertSame([], $erros);
    }

    public function testManifestSemCamposObrigatoriosTemErros(): void
    {
        $erros = ManifestValidator::validate([]);

        $this->assertNotEmpty($erros);
        $this->assertContains('Campo obrigatório em falta: name', $erros);
        $this->assertContains('Campo obrigatório em falta: slug', $erros);
    }
}
