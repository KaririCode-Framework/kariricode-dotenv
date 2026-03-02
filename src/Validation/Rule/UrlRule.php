<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Validation\Rule;

use KaririCode\Dotenv\Contract\ValidationRule;

final readonly class UrlRule implements ValidationRule
{
    public function passes(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    public function message(): string
    {
        return '{name} must be a valid URL.';
    }
}
