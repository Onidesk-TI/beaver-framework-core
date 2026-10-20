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
 *         $hash = hash_make('password123');
 *         hash_check('password123', $hash);
 *
 *   2) Container (controlo, testes, injeção)
 *         $hasher = app()->make(Hasher::class);
 *         $hasher->make('password123');
 *
 *   3) Injeção de dependências (controllers, services)
 *         public function __construct(private Hasher $hasher) {}
 *
 * A sintaxe é inspirada no Laravel, mas o Beaver usa helpers globais
 * e container em vez de facades estáticas. Isto mantém o código
 * testável, explícito e consistente com o resto do framework.
 */

namespace Beaver\Hashing;

interface Hasher
{
    public function make(string $value): string;
    public function check(string $value, string $hash): bool;
    public function needsRehash(string $hash): bool;
}
