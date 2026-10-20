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
 *   1) Helper global (mais curto, uso comum)
 *         log_info('Utilizador registado', ['id' => 42]);
 *         log_error('BD falhou', ['error' => $e->getMessage()]);
 *
 *   2) Container (controlo, testes, injeção)
 *         $logger = app()->make(Logger::class);
 *         $logger->info('Mensagem');
 *
 *   3) Injeção de dependências (controllers, services)
 *         public function __construct(private Logger $logger) {}
 */

namespace Beaver\Logging;

use Beaver\Logging\Contracts\LogDriver;

class Logger
{
    public function __construct(
        private LogDriver $driver,
    ) {
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $this->driver->log(strtoupper($level), $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * Devolve o driver atual.
     */
    public function driver(): LogDriver
    {
        return $this->driver;
    }
}
