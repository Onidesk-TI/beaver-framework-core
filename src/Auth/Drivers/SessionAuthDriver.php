<?php

declare(strict_types=1);

namespace Beaver\Auth\Drivers;

use App\Models\User;
use Beaver\Auth\Contracts\AuthDriver;

class SessionAuthDriver implements AuthDriver
{
    private string $sessionKey = '_auth_id';
    private ?User $cachedUser = null;
    private bool $userLoaded = false;

    public function check(): bool
    {
        return $this->id() !== null;
    }

    public function user(): ?object
    {
        if ($this->userLoaded) {
            return $this->cachedUser;
        }

        $this->userLoaded = true;
        $id = $this->id();

        if ($id === null) {
            $this->cachedUser = null;
            return null;
        }

        $this->cachedUser = User::find($id);

        if ($this->cachedUser === null) {
            $this->logout();
        }

        return $this->cachedUser;
    }

    public function id(): int|string|null
    {
        $this->ensureSession();
        $id = $_SESSION[$this->sessionKey] ?? null;

        return $id === null ? null : (int) $id;
    }

    public function attempt(array $credentials): bool
    {
        $this->ensureSession();

        $email    = (string) ($credentials['email'] ?? '');
        $password = (string) ($credentials['password'] ?? '');

        if ($email === '' || $password === '') {
            return false;
        }

        $user = User::findByEmail($email);

        if ($user === null) {
            return false;
        }

        if (!$user->is_active) {
            return false;
        }

        if (!hash_check($password, (string) $user->password)) {
            return false;
        }

        $_SESSION[$this->sessionKey] = (int) $user->id;

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $this->cachedUser = $user;
        $this->userLoaded = true;

        return true;
    }

    public function logout(): void
    {
        $this->ensureSession();
        unset($_SESSION[$this->sessionKey]);
        $this->cachedUser = null;
        $this->userLoaded = false;
    }

    private function ensureSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
    }
}
