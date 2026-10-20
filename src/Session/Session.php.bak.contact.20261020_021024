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
 *         session_put('user_id', 42);
 *         session_get('user_id');
 *         session_flash('message', 'Bem-vindo');
 *
 *   2) Container (controlo, testes, injeção)
 *         $session = app()->make(Session::class);
 *         $session->put('user_id', 42);
 *
 *   3) Injeção de dependências (controllers, services)
 *         public function __construct(private Session $session) {}
 *
 * A sintaxe é inspirada no Laravel, mas o Beaver usa helpers globais
 * e container em vez de facades estáticas. Isto mantém o código
 * testável, explícito e consistente com o resto do framework.
 */

namespace Beaver\Session;

use Beaver\Session\Drivers\NativeDriver;
use Beaver\Session\Drivers\SessionDriver;

class Session
{
    /** @var array<string, mixed>  Dados atuais da sessão (cache em memória) */
    private array $data = [];

    /** @var array<string, true>  Chaves que foram "flashadas" nesta request */
    private array $flashNew = [];

    /** @var array<string, true>  Chaves flash da request anterior (para apagar no fim) */
    private array $flashOld = [];

    private bool $started = false;

    public function __construct(
        private ?SessionDriver $driver = null,
        private string $flashKey = '_flash',
    ) {
        $this->driver ??= new NativeDriver();
    }

    // ─────────────────────────────────────────────────────────
    // Ciclo de vida
    // ─────────────────────────────────────────────────────────

    /**
     * Inicia a sessão. Carrega os dados do driver.
     */
    public function start(): void
    {
        if ($this->started) {
            return;
        }

        $this->driver->start();

        // Dados gerais
        $this->data = $this->driver->all();

        // Rotação de flash: os "new" da request anterior tornam-se "old"
        $oldFlash = $this->data[$this->flashKey] ?? [];
        unset($this->data[$this->flashKey]);

        $this->flashOld = is_array($oldFlash) ? array_fill_keys(array_keys($oldFlash), true) : [];
        $this->flashNew = [];

        $this->started = true;
    }

    /**
     * Persiste os dados e roda os flash.
     *
     * Chamado no fim da request (pelo Kernel).
     */
    public function save(): void
    {
        if (!$this->started) {
            return;
        }

        // Apagar flash antigos (já sobreviveram uma request)
        foreach (array_keys($this->flashOld) as $key) {
            unset($this->data[$key]);
        }

        // Guardar os flash novos
        if ($this->flashNew !== []) {
            $this->data[$this->flashKey] = array_fill_keys(array_keys($this->flashNew), true);
        } else {
            unset($this->data[$this->flashKey]);
        }

        $this->driver->replace($this->data);
    }

    // ─────────────────────────────────────────────────────────
    // API principal
    // ─────────────────────────────────────────────────────────

    public function put(string $key, mixed $value): void
    {
        $this->start();
        $this->data[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();
        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        $this->start();
        return array_key_exists($key, $this->data);
    }

    public function forget(string $key): void
    {
        $this->start();
        unset($this->data[$key]);
    }

    /**
     * Devolve o valor E apaga-o imediatamente.
     */
    public function pull(string $key, mixed $default = null): mixed
    {
        $this->start();
        $value = $this->data[$key] ?? $default;
        unset($this->data[$key]);
        return $value;
    }

    /**
     * Devolve todos os dados.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $this->start();
        return $this->data;
    }

    /**
     * Apaga todos os dados.
     */
    public function flush(): void
    {
        $this->start();
        $this->data      = [];
        $this->flashNew  = [];
        $this->flashOld  = [];
    }

    // ─────────────────────────────────────────────────────────
    // Flash
    // ─────────────────────────────────────────────────────────

    /**
     * Flash: o valor fica disponível na próxima request, depois é apagado.
     */
    public function flash(string $key, mixed $value): void
    {
        $this->start();
        $this->data[$key]     = $value;
        $this->flashNew[$key] = true;
        unset($this->flashOld[$key]);
    }

    /**
     * Devolve o valor do flash E apaga-o (já não volta na próxima request).
     */
    public function pullFlash(string $key, mixed $default = null): mixed
    {
        $this->start();
        $value = $this->data[$key] ?? $default;
        unset($this->data[$key], $this->flashNew[$key], $this->flashOld[$key]);
        return $value;
    }

    /**
     * Regenera o ID da sessão (segurança pós-login).
     */
    public function regenerate(): void
    {
        $this->start();
        $this->driver->regenerate();
    }

    /**
     * Destrói a sessão completamente.
     */
    public function destroy(): void
    {
        $this->start();
        $this->data     = [];
        $this->flashNew = [];
        $this->flashOld = [];
        $this->driver->destroy();
    }

    /**
     * Devolve o ID da sessão atual.
     */
    public function id(): string
    {
        $this->start();
        return $this->driver->id();
    }
}
