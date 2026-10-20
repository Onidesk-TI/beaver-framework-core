<?php

declare(strict_types=1);

namespace Beaver\Validation\Rules;

use Beaver\Validation\Rule;

class ConfirmedRule implements Rule
{
    public function validate(
        mixed $value,
        array $data,
        string $field,
        string $fieldLabel,
        array $params = [],
    ): ?string {
        $confirmationField = $field . '_confirmation';
        if (!isset($data[$confirmationField]) || $data[$confirmationField] !== $value) {
            return __('validation.confirmed', ['field' => $fieldLabel]);
        }
        return null;
    }
}
