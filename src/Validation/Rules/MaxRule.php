<?php

declare(strict_types=1);

namespace Beaver\Validation\Rules;

use Beaver\Validation\Rule;

class MaxRule implements Rule
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

        $max = (int) ($params[0] ?? 0);

        if (is_numeric($value)) {
            if ((float) $value > $max) {
                return __('validation.max.numeric', ['field' => $fieldLabel, 'max' => $max]);
            }
        } else {
            if (mb_strlen((string) $value) > $max) {
                return __('validation.max.string', ['field' => $fieldLabel, 'max' => $max]);
            }
        }
        return null;
    }
}
