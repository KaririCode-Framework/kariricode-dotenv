<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Validation\Rule;

use KaririCode\Dotenv\Contract\ValidationRule;

final readonly class IsIntegerRule implements ValidationRule
{
    public function passes(string $value): bool
    {
        return preg_match('/\A[+-]?\d+\z/', $value) === 1;
    }

    public function message(): string
    {
        return '{name} must be an integer.';
    }
}
