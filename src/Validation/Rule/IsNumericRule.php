<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Validation\Rule;

use KaririCode\Dotenv\Contract\ValidationRule;

final readonly class IsNumericRule implements ValidationRule
{
    #[\Override]
    public function passes(string $value): bool
    {
        return is_numeric($value);
    }

    #[\Override]
    public function message(): string
    {
        return '{name} must be numeric.';
    }
}
