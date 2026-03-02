<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Validation\Rule;

use KaririCode\Dotenv\Contract\ValidationRule;

final readonly class IsBooleanRule implements ValidationRule
{
    private const array ACCEPTED = ['true', 'false', '1', '0', 'yes', 'no', 'on', 'off'];

    public function passes(string $value): bool
    {
        return in_array(strtolower($value), self::ACCEPTED, true);
    }

    public function message(): string
    {
        return '{name} must be a boolean (true/false, yes/no, on/off, 1/0).';
    }
}
