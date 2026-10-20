<?php

declare(strict_types=1);

namespace Beaver\Validation\Rules;

use Beaver\Validation\Rule;

class InRule implements Rule
{
    public function validate(
        mixed $value,
        array $data,
        string $field,
        string $fieldLabel,
        array $params = [],
    ): ?string {
        if ($value === null || $value === '') {
            return null;
        }
        $allowed = array_map('strval', $params);
        if (!in_array((string) $value, $allowed, true)) {
            return __('validation.in', ['field' => $fieldLabel, 'values' => implode(', ', $allowed)]);
        }
        return null;
    }
}
