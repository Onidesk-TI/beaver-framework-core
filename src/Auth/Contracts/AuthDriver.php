<?php

declare(strict_types=1);

namespace Beaver\Auth\Contracts;

interface AuthDriver
{
    public function check(): bool;
    public function user(): ?object;
    public function id(): int|string|null;
    public function attempt(array $credentials): bool;
    public function logout(): void;
}
