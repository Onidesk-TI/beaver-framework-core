<?php

/**
 * Beaver Framework — Modern PHP framework with a plugin ecosystem.
 *
 * @package    Beaver Framework
 * @author     Franco <onidesk@outlook.com>
 * @copyright  2026 Onidesk
 * @license    MIT <https://opensource.org/licenses/MIT>
 * @link       https://github.com/Onidesk-TI/beaver-framework
 */

namespace Beaver\Database\Model\Casts;

/**
 * Contrato para classes que aplicam um cast a um valor.
 *
 * Usado pelo Model para converter valores lidos da BD em objetos
 * PHP mais úteis (ex: \DateTimeImmutable, arrays de JSON).
 */
interface CastInterface
{
    /**
     * Aplica o cast ao valor cru vindo da BD.
     *
     * @param mixed $value Valor tal como veio da BD
     * @return mixed       Valor convertido
     */
    public static function apply(mixed $value): mixed;
}
