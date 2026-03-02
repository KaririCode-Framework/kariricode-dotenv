<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Detector;

use KaririCode\Dotenv\Contract\TypeDetector;
use KaririCode\Dotenv\Enum\ValueType;

/**
 * Detects float values: digits with decimal point and optional exponent.
 *
 * @package KaririCode\Dotenv
 * @since   4.0.0 ARFA 1.3
 */
final readonly class FloatDetector implements TypeDetector
{
    public function priority(): int
    {
        return 170;
    }

    public function detect(string $value): ?ValueType
    {
        if ($value === '') {
            return null;
        }

        // Must contain a dot or exponent marker to be float (not integer)
        if (!str_contains($value, '.') && !str_contains(strtolower($value), 'e')) {
            return null;
        }

        return preg_match('/\A[+-]?(\d+\.?\d*|\d*\.?\d+)([eE][+-]?\d+)?\z/', $value) === 1
            ? ValueType::Float
            : null;
    }
}
