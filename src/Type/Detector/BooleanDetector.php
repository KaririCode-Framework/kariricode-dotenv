<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Detector;

use KaririCode\Dotenv\Contract\TypeDetector;
use KaririCode\Dotenv\Enum\ValueType;

/**
 * Detects boolean literals: true/false, yes/no, on/off (case-insensitive).
 *
 * Uses normalized lowercase comparison to match the same values the
 * BooleanCaster handles, avoiding asymmetry between detection and casting.
 *
 * @package KaririCode\Dotenv
 * @since   4.0.0 ARFA 1.3
 */
final readonly class BooleanDetector implements TypeDetector
{
    private const array BOOLEAN_LITERALS = [
        'true', 'false', 'yes', 'no', 'on', 'off',
    ];

    private const array BOOLEAN_PARENTHESIZED = ['(true)', '(false)'];

    #[\Override]
    public function priority(): int
    {
        return 190;
    }

    #[\Override]
    public function detect(string $value): ?ValueType
    {
        if (\in_array(strtolower($value), self::BOOLEAN_LITERALS, true)) {
            return ValueType::Boolean;
        }

        return \in_array($value, self::BOOLEAN_PARENTHESIZED, true) ? ValueType::Boolean : null;
    }
}
