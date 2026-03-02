<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Validation\Rule;

use KaririCode\Dotenv\Contract\ValidationRule;

final readonly class BetweenRule implements ValidationRule
{
    public function __construct(
        private int|float $min,
        private int|float $max,
    ) {
    }

    #[\Override]
    public function passes(string $value): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        $numeric = $value + 0;

        return $numeric >= $this->min && $numeric <= $this->max;
    }

    #[\Override]
    public function message(): string
    {
        return "{name} must be between {$this->min} and {$this->max}.";
    }
}
