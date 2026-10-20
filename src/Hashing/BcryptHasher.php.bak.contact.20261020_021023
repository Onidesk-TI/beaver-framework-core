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

class BcryptHasher implements Hasher
{
    private int $rounds;

    public function __construct(int $rounds = 12)
    {
        if ($rounds < 4 || $rounds > 31) {
            throw new \InvalidArgumentException(
                "Bcrypt rounds must be between 4 and 31, got: {$rounds}"
            );
        }

        $this->rounds = $rounds;
    }

    public function make(string $value): string
    {
        $hash = password_hash($value, PASSWORD_BCRYPT, [
            'cost' => $this->rounds,
        ]);

        if ($hash === false) {
            throw new \RuntimeException('Failed to hash value with bcrypt');
        }

        return $hash;
    }

    public function check(string $value, string $hash): bool
    {
        if ($value === '' || $hash === '') {
            return false;
        }

        return password_verify($value, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, [
            'cost' => $this->rounds,
        ]);
    }
}
