<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\ValueObject;

use KaririCode\Dotenv\Enum\ValueType;

/**
 * Immutable representation of a single parsed environment variable.
 *
 * Holds the raw string, detected type, cast PHP value, and source metadata.
 * Once constructed, no mutation is possible — ARFA 1.3 P1 compliant.
 *
 * @package KaririCode\Dotenv
 * @since   4.0.0 ARFA 1.3
 */
final readonly class EnvironmentVariable
{
    /**
     * @param string    $name       Variable name.
     * @param string    $rawValue   Original string from the .env file.
     * @param ValueType $type       Detected type.
     * @param mixed     $value      Cast PHP value.
     * @param string    $source     File path or label (e.g., ".env.local", "cache", "environment").
     * @param bool      $overridden True if this value replaced a previous source.
     */
    public function __construct(
        public string $name,
        public string $rawValue,
        public ValueType $type,
        public mixed $value,
        public string $source = '',
        public bool $overridden = false,
    ) {
    }
}
