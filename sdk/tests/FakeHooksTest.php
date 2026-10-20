<?php
declare(strict_types=1);

namespace Beaver\Sdk\Tests;

use Beaver\Sdk\Testing\FakeHooks;
use PHPUnit\Framework\TestCase;

final class FakeHooksTest extends TestCase
{
    public function testEmitRegistaEvento(): void
    {
        $hooks = new FakeHooks();
        $hooks->emit('app.boot', 'arg1', 'arg2');

        $this->assertTrue($hooks->wasEmitted('app.boot'));

        $emitted = $hooks->emitted('app.boot');
        $this->assertCount(1, $emitted);
        $this->assertSame('app.boot', $emitted[0]['event']);
        $this->assertSame(['arg1', 'arg2'], $emitted[0]['args']);
    }

    public function testResetLimpaEventos(): void
    {
        $hooks = new FakeHooks();
        $hooks->emit('app.boot');
        $hooks->reset();

        $this->assertFalse($hooks->wasEmitted('app.boot'));
        $this->assertSame([], $hooks->allEmitted());
    }
}
