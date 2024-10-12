<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Caster;

use KaririCode\Dotenv\Contract\Type\TypeCaster;

class FloatCaster implements TypeCaster
{
    public function canCast(mixed $value): bool
    {
        return is_numeric($value) && false !== strpos((string) $value, '.');
    }

    public function cast(mixed $value): float
    {
        return (float) $value;
    }
}
