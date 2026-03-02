<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Caster;

use KaririCode\Dotenv\Contract\TypeCaster;

/**
 * @package KaririCode\Dotenv
 * @since   4.0.0 ARFA 1.3
 */
final readonly class BooleanCaster implements TypeCaster
{
    private const array TRUE_VALUES = ['true', 'yes', 'on', '(true)'];

    public function cast(string $value): bool
    {
        return in_array(strtolower($value), self::TRUE_VALUES, true);
    }
}
