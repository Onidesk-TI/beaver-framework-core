<?php

declare(strict_types=1);

namespace Beaver\Validation\Rules;

use Beaver\Database\Database;
use Beaver\Validation\Rule;

class UniqueRule implements Rule
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

        $table = $params[0] ?? '';
        if ($table === '') {
            return null;
        }

        $column   = $params[1] ?? $field;
        $except   = $params[2] ?? null;
        $exceptId = $params[3] ?? null;

        $sql = sprintf(
            'SELECT COUNT(*) AS c FROM %s WHERE %s = :value',
            $this->quote($table),
            $this->quote($column),
        );
        $bindings = [':value' => $value];

        if ($except !== null && $exceptId !== null) {
            $sql .= sprintf(' AND %s != :except_id', $this->quote($except));
            $bindings[':except_id'] = $exceptId;
        }

        $row   = Database::getInstance()->selectOne($sql, $bindings);
        $count = (int) ($row['c'] ?? 0);

        if ($count > 0) {
            return __('validation.unique', ['field' => $fieldLabel]);
        }
        return null;
    }

    private function quote(string $name): string
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            throw new \InvalidArgumentException("Invalid identifier: {$name}");
        }
        return '"' . $name . '"';
    }
}
