<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Validation\Rule;

use KaririCode\Dotenv\Contract\ValidationRule;

final readonly class MatchesRegexRule implements ValidationRule
{
    public function __construct(
        private string $pattern,
    ) {
    }

    public function passes(string $value): bool
    {
        return preg_match($this->pattern, $value) === 1;
    }

    public function message(): string
    {
        return "{name} must match pattern {$this->pattern}.";
    }
}
