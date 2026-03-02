<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Detector;

use KaririCode\Dotenv\Contract\TypeDetector;
use KaririCode\Dotenv\Enum\ValueType;

/**
 * Detects null literals: "null", "NULL", "(null)".
 *
 * Empty string ('') is intentionally excluded — `FOO=` must yield an
 * empty string, not null. This matches POSIX behavior and prevents
 * data loss when distinguishing between `FOO=` and `FOO=null`.
 *
 * @package KaririCode\Dotenv
 * @since   4.0.0 ARFA 1.3
 */
final readonly class NullDetector implements TypeDetector
{
    private const array NULL_LITERALS = ['null', 'NULL', '(null)'];

    #[\Override]
    public function priority(): int
    {
        return 200;
    }

    #[\Override]
    public function detect(string $value): ?ValueType
    {
        return in_array($value, self::NULL_LITERALS, true) ? ValueType::Null : null;
    }
}
