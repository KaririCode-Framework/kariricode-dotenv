<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Validation\Rule;

use KaririCode\Dotenv\Contract\ValidationRule;

final readonly class AllowedValuesRule implements ValidationRule
{
    /** @param list<string> $allowed */
    public function __construct(
        private array $allowed,
    ) {
    }

    #[\Override]
    public function passes(string $value): bool
    {
        return \in_array($value, $this->allowed, true);
    }

    #[\Override]
    public function message(): string
    {
        $list = implode(', ', $this->allowed);

        return "{name} must be one of: {$list}.";
    }
}
