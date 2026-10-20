<?php

declare(strict_types=1);

namespace Beaver\Validation\Rules;

use Beaver\Validation\Rule;

class MinRule implements Rule
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

        $min = (int) ($params[0] ?? 0);

        if (is_numeric($value)) {
            if ((float) $value < $min) {
                return __('validation.min.numeric', ['field' => $fieldLabel, 'min' => $min]);
            }
        } else {
            if (mb_strlen((string) $value) < $min) {
                return __('validation.min.string', ['field' => $fieldLabel, 'min' => $min]);
            }
        }
        return null;
    }
}
