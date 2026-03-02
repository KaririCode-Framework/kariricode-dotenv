<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Detector;

use KaririCode\Dotenv\Contract\TypeDetector;
use KaririCode\Dotenv\Enum\ValueType;

/**
 * Detects JSON object values: strings wrapped in curly braces that decode successfully.
 *
 * @package KaririCode\Dotenv
 * @since   4.0.0 ARFA 1.3
 */
final readonly class JsonDetector implements TypeDetector
{
    public function priority(): int
    {
        return 160;
    }

    public function detect(string $value): ?ValueType
    {
        $trimmed = trim($value);

        if (!str_starts_with($trimmed, '{') || !str_ends_with($trimmed, '}')) {
            return null;
        }

        json_decode($trimmed);

        return json_last_error() === JSON_ERROR_NONE ? ValueType::Json : null;
    }
}
