<?php

declare(strict_types=1);

namespace Beaver\Validation;

use Beaver\Validation\Rules\BetweenRule;
use Beaver\Validation\Rules\BooleanRule;
use Beaver\Validation\Rules\ConfirmedRule;
use Beaver\Validation\Rules\DateRule;
use Beaver\Validation\Rules\EmailRule;
use Beaver\Validation\Rules\InRule;
use Beaver\Validation\Rules\IntegerRule;
use Beaver\Validation\Rules\MaxRule;
use Beaver\Validation\Rules\MinRule;
use Beaver\Validation\Rules\NumericRule;
use Beaver\Validation\Rules\RegexRule;
use Beaver\Validation\Rules\RequiredRule;
use Beaver\Validation\Rules\StringRule;
use Beaver\Validation\Rules\UniqueRule;
use Beaver\Validation\Rules\UrlRule;

class RuleFactory
{
    /** @var array<string, class-string<Rule>> */
    private const RULES = [
        'required'  => RequiredRule::class,
        'email'     => EmailRule::class,
        'min'       => MinRule::class,
        'max'       => MaxRule::class,
        'integer'   => IntegerRule::class,
        'numeric'   => NumericRule::class,
        'boolean'   => BooleanRule::class,
        'bool'      => BooleanRule::class,
        'string'    => StringRule::class,
        'date'      => DateRule::class,
        'in'        => InRule::class,
        'regex'     => RegexRule::class,
        'confirmed' => ConfirmedRule::class,
        'url'       => UrlRule::class,
        'between'   => BetweenRule::class,
        'unique'    => UniqueRule::class,
    ];

    public static function make(string $rule): ?Rule
    {
        $parts = explode(':', $rule, 2);
        $name  = strtolower(trim($parts[0]));

        $class = self::RULES[$name] ?? null;

        return $class ? new $class() : null;
    }

    /** @return array<int, string> */
    public static function params(string $rule): array
    {
        $parts = explode(':', $rule, 2);

        if (!isset($parts[1])) {
            return [];
        }

        return array_map('trim', explode(',', $parts[1]));
    }
}
