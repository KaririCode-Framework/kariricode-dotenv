<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Detector;

use KaririCode\Dotenv\Contract\TypeDetector;
use KaririCode\Dotenv\Enum\ValueType;

/**
 * Detects JSON array values: strings wrapped in square brackets that decode successfully.
 *
 * @package KaririCode\Dotenv
 * @since   4.0.0 ARFA 1.3
 */
final readonly class ArrayDetector implements TypeDetector
{
    public function priority(): int
    {
        return 150;
    }

    public function detect(string $value): ?ValueType
    {
        $trimmed = trim($value);

        if (!str_starts_with($trimmed, '[') || !str_ends_with($trimmed, ']')) {
            return null;
        }

        $decoded = json_decode($trimmed, true);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? ValueType::Array : null;
    }
}
