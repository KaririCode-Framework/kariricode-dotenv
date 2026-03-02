<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Validation\Rule;

use KaririCode\Dotenv\Contract\ValidationRule;

final readonly class EmailRule implements ValidationRule
{
    #[\Override]
    public function passes(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    #[\Override]
    public function message(): string
    {
        return '{name} must be a valid email address.';
    }
}
