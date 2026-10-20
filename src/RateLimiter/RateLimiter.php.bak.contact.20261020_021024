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
 *         $limiter = rate_limiter('login', 5, 60);
 *         if ($limiter->tooManyAttempts($ip)) { ... }
 *
 *   2) Container (controlo)
 *         $limiter = app()->make(RateLimiter::class);
 *         $limiter->setLimit(5, 60);
 *
 *   3) Injeção de dependências
 *         public function __construct(private RateLimiter $limiter) {}
 */

namespace Beaver\RateLimiter;

use Beaver\RateLimiter\Contracts\Storage;

class RateLimiter
{
    private int $maxAttempts = 60;
    private int $decaySeconds = 60;

    public function __construct(
        private Storage $storage,
    ) {
    }

    /**
     * Configura o limite.
     */
    public function setLimit(int $maxAttempts, int $decaySeconds): self
    {
        $this->maxAttempts  = $maxAttempts;
        $this->decaySeconds = $decaySeconds;

        return $this;
    }

    /**
     * Regista uma tentativa (incrementa).
     *
     * @return int Novo número de tentativas
     */
    public function hit(string $key): int
    {
        return $this->storage->hit($key, $this->decaySeconds);
    }

    /**
     * Regista + devolve true se ainda pode tentar.
     */
    public function attempt(string $key): bool
    {
        $this->hit($key);

        return !$this->tooManyAttempts($key);
    }

    /**
     * Já ultrapassou o limite?
     */
    public function tooManyAttempts(string $key): bool
    {
        return $this->attempts($key) >= $this->maxAttempts;
    }

    /**
     * Nº de tentativas feitas.
     */
    public function attempts(string $key): int
    {
        return $this->storage->count($key);
    }

    /**
     * Tentativas restantes.
     */
    public function remaining(string $key): int
    {
        return max(0, $this->maxAttempts - $this->attempts($key));
    }

    /**
     * Segundos até poder tentar de novo (0 se já pode).
     */
    public function availableIn(string $key): int
    {
        $expiresAt = $this->storage->availableAt($key);

        if ($expiresAt === null) {
            return 0;
        }

        return max(0, $expiresAt - time());
    }

    /**
     * Reset — limpa o contador.
     */
    public function clear(string $key): void
    {
        $this->storage->clear($key);
    }

    /**
     * Limite máximo.
     */
    public function maxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * Tempo de decaimento.
     */
    public function decaySeconds(): int
    {
        return $this->decaySeconds;
    }
}
