<?php

declare(strict_types=1);

namespace Beaver\Validation\Rules;

use Beaver\Validation\Rule;

class BooleanRule implements Rule
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
        if (!in_array($value, [true, false, 0, 1, '0', '1'], true)) {
            return __('validation.boolean', ['field' => $fieldLabel]);
        }
        return null;
    }
}
