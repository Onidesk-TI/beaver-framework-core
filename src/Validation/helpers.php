<?php

declare(strict_types=1);

/**
 * Helper global de validação.
 *
 * @param  array $data
 * @param  array $rules
 * @param  array $labels
 * @return array<string, array<int, string>>
 */

if (!function_exists('validate')) {
    function validate(array $data, array $rules, array $labels = []): array
    {
        return (new \Beaver\Validation\Validator($data, $rules, $labels))->errors();
    }
}
