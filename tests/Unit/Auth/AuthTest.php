<?php
declare(strict_types=1);

namespace Tests\Unit\Auth;

use Beaver\Auth\Auth;
use Beaver\Auth\Contracts\AuthDriver;
use PHPUnit\Framework\TestCase;

final class AuthTest extends TestCase
{
    /** Driver mock configurável para cada teste. */
    private function makeDriver(array $config = []): AuthDriver
    {
        return new class($config) implements AuthDriver {
            public bool $loggedOut = false;

            public function __construct(private array $config) {}

            public function check(): bool
            {
                return $this->config['check'] ?? false;
            }

            public function user(): ?object
            {
                return $this->config['user'] ?? null;
            }

            public function id(): int|string|null
            {
                $u = $this->user();
                return $u?->id ?? null;
            }

            public function attempt(array $credentials): bool
            {
                return $this->config['attempt'] ?? false;
            }

            public function logout(): void
            {
                $this->loggedOut = true;
                $this->config['check'] = false;
                $this->config['user'] = null;
            }
        };
    }

    public function testCheckEGuest(): void
    {
        Auth::register($this->makeDriver(['check' => true]));
        $this->assertTrue(Auth::check());
        $this->assertFalse(Auth::guest());
    }

    public function testGuestQuandoNaoAutenticado(): void
    {
        Auth::register($this->makeDriver(['check' => false]));
        $this->assertFalse(Auth::check());
        $this->assertTrue(Auth::guest());
    }

    public function testUserEDevolveObjeto(): void
    {
        $user = (object) ['id' => 42, 'name' => 'Franco'];
        Auth::register($this->makeDriver(['check' => true, 'user' => $user]));

        $this->assertSame($user, Auth::user());
        $this->assertSame(42, Auth::id());
    }

    public function testUserDevolveNullSeNaoAutenticado(): void
    {
        Auth::register($this->makeDriver(['check' => false]));
        $this->assertNull(Auth::user());
        $this->assertNull(Auth::id());
    }

    public function testAttempt(): void
    {
        Auth::register($this->makeDriver(['attempt' => true]));
        $this->assertTrue(Auth::attempt(['email' => 'a@b.c', 'password' => 'x']));
    }

    public function testLogout(): void
    {
        $driver = $this->makeDriver(['check' => true, 'user' => (object)['id' => 1]]);
        Auth::register($driver);

        $this->assertTrue(Auth::check());
        Auth::logout();
        $this->assertTrue($driver->loggedOut);
        $this->assertFalse(Auth::check());
    }
}
