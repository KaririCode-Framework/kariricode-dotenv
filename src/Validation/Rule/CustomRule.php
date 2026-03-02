<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Validation\Rule;

use KaririCode\Dotenv\Contract\ValidationRule;

final readonly class CustomRule implements ValidationRule
{
    /** @param \Closure(string): bool $callback */
    public function __construct(
        private \Closure $callback,
        private string $failureMessage = '{name} failed custom validation.',
    ) {
    }

    #[\Override]
    public function passes(string $value): bool
    {
        return ($this->callback)($value);
    }

    #[\Override]
    public function message(): string
    {
        return $this->failureMessage;
    }
}
