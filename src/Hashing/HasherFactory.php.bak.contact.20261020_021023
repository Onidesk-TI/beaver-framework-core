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

namespace Beaver\Hashing;

class HasherFactory
{
    public static function fromConfig(array $config): Hasher
    {
        $driver = $config['driver'] ?? 'bcrypt';

        return match ($driver) {
            'bcrypt' => new BcryptHasher(
                (int) ($config['bcrypt']['rounds'] ?? 12)
            ),

            'argon2', 'argon2id' => new Argon2Hasher(
                (int) ($config['argon2']['memory']  ?? 65536),
                (int) ($config['argon2']['time']    ?? 4),
                (int) ($config['argon2']['threads'] ?? 1),
            ),

            default => throw new \InvalidArgumentException(
                "Unknown hasher driver: {$driver}"
            ),
        };
    }
}
