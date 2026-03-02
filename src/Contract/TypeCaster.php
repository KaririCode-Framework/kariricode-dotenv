<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Contract;

/**
 * Converts a raw environment variable string into a typed PHP value.
 *
 * Each caster is responsible for exactly one ValueType.
 * Casters must be deterministic: same input → same output.
 *
 * @package KaririCode\Dotenv
 * @since   4.0.0 ARFA 1.3
 */
interface TypeCaster
{
    /** Casts the raw string value into the appropriate PHP type. */
    public function cast(string $value): mixed;
}
