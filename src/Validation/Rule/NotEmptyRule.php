<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Validation\Rule;

use KaririCode\Dotenv\Contract\ValidationRule;

final readonly class NotEmptyRule implements ValidationRule
{
    public function passes(string $value): bool
    {
        return trim($value) !== '';
    }

    public function message(): string
    {
        return '{name} must not be empty.';
    }
}
