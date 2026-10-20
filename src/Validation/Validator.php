<?php

declare(strict_types=1);

namespace Beaver\Validation;

class Validator
{
    /** @var array<string, array<int, string>> */
    private array $errors = [];

    public function __construct(
        private array $data,
        private array $rules,
        private array $labels = [],
    ) {
    }

    /** @return array<string, array<int, string>> */
    public function errors(): array
    {
        $this->errors = [];

        foreach ($this->rules as $field => $rulesString) {
            $value      = $this->data[$field] ?? null;
            $fieldLabel = $this->labels[$field] ?? $field;

            $rules = is_array($rulesString)
                ? $rulesString
                : explode('|', $rulesString);

            foreach ($rules as $ruleString) {
                $ruleString = trim($ruleString);

                if ($ruleString === '') {
                    continue;
                }

                $rule = RuleFactory::make($ruleString);

                if ($rule === null) {
                    continue;
                }

                $params = RuleFactory::params($ruleString);

                $error = $rule->validate(
                    $value,
                    $this->data,
                    $field,
                    $fieldLabel,
                    $params,
                );

                if ($error !== null) {
                    $this->errors[$field][] = $error;

                    if (str_starts_with($ruleString, 'required')) {
                        break;
                    }
                }
            }
        }

        return $this->errors;
    }

    public function fails(): bool
    {
        return $this->errors() !== [];
    }

    public function passes(): bool
    {
        return $this->errors() === [];
    }
}
