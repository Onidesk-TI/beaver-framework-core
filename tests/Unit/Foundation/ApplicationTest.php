<?php
declare(strict_types=1);

namespace Tests\Unit\Foundation;

use Beaver\Foundation\Application;
use PHPUnit\Framework\TestCase;

final class ApplicationTest extends TestCase
{
    /** Cria uma Application sem correr o construtor (sem .env/Config/View). */
    private function makeBareApplication(): Application
    {
        $ref = new \ReflectionClass(Application::class);
        return $ref->newInstanceWithoutConstructor();
    }

    public function testBindEMake(): void
    {
        $app = $this->makeBareApplication();
        $app->bind('foo', fn() => 'bar');

        $this->assertSame('bar', $app->make('foo'));
    }

    public function testMakeCacheiaBindings(): void
    {
        $app = $this->makeBareApplication();
        $count = 0;
        $app->bind('contador', function () use (&$count) {
            $count++;
            return $count;
        });

        $this->assertSame(1, $app->make('contador'));
        $this->assertSame(1, $app->make('contador')); // não volta a correr
    }

    public function testInstanceTemPrioridadeSobreBind(): void
    {
        $app = $this->makeBareApplication();
        $app->bind('foo', fn() => 'binding');
        $app->instance('foo', 'instance');

        $this->assertSame('instance', $app->make('foo'));
    }

    public function testMakeInstanciaClasseExistente(): void
    {
        $app = $this->makeBareApplication();
        $obj = $app->make(\stdClass::class);

        $this->assertInstanceOf(\stdClass::class, $obj);
    }

    public function testMakeLancaExcecaoSeNaoResolve(): void
    {
        $app = $this->makeBareApplication();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot resolve: Classe\\Inexistente');

        $app->make('Classe\\Inexistente');
    }
}
