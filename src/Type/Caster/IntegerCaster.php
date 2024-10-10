<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Caster;

use KaririCode\Dotenv\Contract\TypeCaster;

class IntegerCaster implements TypeCaster
{
    public function canCast(mixed $value): bool
    {
        return is_numeric($value) && (string) (int) $value === (string) $value;
    }

    public function cast(mixed $value): int
    {
        return (int) $value;
    }
}
