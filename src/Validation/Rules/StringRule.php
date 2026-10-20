<?php

declare(strict_types=1);

namespace Beaver\Validation\Rules;

use Beaver\Validation\Rule;

class StringRule implements Rule
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
        if (!is_string($value)) {
            return __('validation.string', ['field' => $fieldLabel]);
        }
        return null;
    }
}
