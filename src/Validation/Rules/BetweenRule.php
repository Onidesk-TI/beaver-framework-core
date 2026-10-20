<?php

declare(strict_types=1);

namespace Beaver\Validation\Rules;

use Beaver\Validation\Rule;

class BetweenRule implements Rule
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

        $min = $params[0] ?? 0;
        $max = $params[1] ?? 0;

        if (is_numeric($value)) {
            $n = (float) $value;
            if ($n < (float) $min || $n > (float) $max) {
                return __('validation.between.numeric', ['field' => $fieldLabel, 'min' => $min, 'max' => $max]);
            }
        } else {
            $len = mb_strlen((string) $value);
            if ($len < (int) $min || $len > (int) $max) {
                return __('validation.between.string', ['field' => $fieldLabel, 'min' => $min, 'max' => $max]);
            }
        }
        return null;
    }
}
