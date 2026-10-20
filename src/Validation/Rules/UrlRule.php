<?php

declare(strict_types=1);

namespace Beaver\Validation\Rules;

use Beaver\Validation\Rule;

class UrlRule implements Rule
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
        if (!filter_var((string) $value, FILTER_VALIDATE_URL)) {
            return __('validation.url', ['field' => $fieldLabel]);
        }
        return null;
    }
}
