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

class Argon2Hasher implements Hasher
{
    private int $memory;
    private int $time;
    private int $threads;

    public function __construct(int $memory = 65536, int $time = 4, int $threads = 1)
    {
        if (!defined('PASSWORD_ARGON2ID')) {
            throw new \RuntimeException(
                'Argon2id is not available in this PHP build'
            );
        }

        $this->memory  = $memory;
        $this->time    = $time;
        $this->threads = $threads;
    }

    public function make(string $value): string
    {
        $hash = password_hash($value, PASSWORD_ARGON2ID, [
            'memory_cost' => $this->memory,
            'time_cost'   => $this->time,
            'threads'     => $this->threads,
        ]);

        if ($hash === false) {
            throw new \RuntimeException('Failed to hash value with argon2id');
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
        return password_needs_rehash($hash, PASSWORD_ARGON2ID, [
            'memory_cost' => $this->memory,
            'time_cost'   => $this->time,
            'threads'     => $this->threads,
        ]);
    }
}
