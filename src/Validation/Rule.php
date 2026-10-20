<?php

declare(strict_types=1);

namespace Beaver\Validation;

interface Rule
{
    public function validate(
        mixed $value,
        array $data,
        string $field,
        string $fieldLabel,
        array $params = [],
    ): ?string;
}
