<?php

declare(strict_types=1);

namespace Beaver\Validation\Rules;

use Beaver\Validation\Rule;

class NumericRule implements Rule
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
        if (!is_numeric($value)) {
            return __('validation.numeric', ['field' => $fieldLabel]);
        }
        return null;
    }
}
