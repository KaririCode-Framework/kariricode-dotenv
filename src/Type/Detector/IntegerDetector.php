<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Detector;

use KaririCode\Dotenv\Contract\TypeDetector;
use KaririCode\Dotenv\Enum\ValueType;

/**
 * Detects integer values: optional sign followed by digits only.
 *
 * @package KaririCode\Dotenv
 * @since   4.0.0 ARFA 1.3
 */
final readonly class IntegerDetector implements TypeDetector
{
    public function priority(): int
    {
        return 180;
    }

    public function detect(string $value): ?ValueType
    {
        if ($value === '' || $value === '-' || $value === '+') {
            return null;
        }

        return preg_match('/\A[+-]?\d+\z/', $value) === 1 ? ValueType::Integer : null;
    }
}
