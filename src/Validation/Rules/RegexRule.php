<?php

declare(strict_types=1);

namespace Beaver\Validation\Rules;

use Beaver\Validation\Rule;

class RegexRule implements Rule
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

        $pattern = $params[0] ?? '';
        if ($pattern === '') {
            return null;
        }

        if (!preg_match('/^[\/#~].*[\/#~][a-z]*$/', $pattern)) {
            $pattern = '/' . str_replace('/', '\/', $pattern) . '/';
        }

        if (!preg_match($pattern, (string) $value)) {
            return __('validation.regex', ['field' => $fieldLabel]);
        }
        return null;
    }
}
