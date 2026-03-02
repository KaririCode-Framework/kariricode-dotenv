<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Contract;

/**
 * Single validation rule applied to an environment variable's raw string value.
 *
 * @package KaririCode\Dotenv
 * @since   4.1.0
 */
interface ValidationRule
{
    /**
     * @return bool True when the value satisfies this rule.
     */
    public function passes(string $value): bool;

    /**
     * Human-readable failure message. The placeholder `{name}` is replaced
     * with the variable name at reporting time.
     */
    public function message(): string;
}
