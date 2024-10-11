<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Caster;

use KaririCode\Dotenv\Contract\TypeCaster;

class BooleanCaster implements TypeCaster
{
    public function canCast(mixed $value): bool
    {
        $value = strtolower($value);

        return in_array($value, [
            'true', 'false', '1', '0', 'yes', 'no', 'on', 'off',
        ], true);
    }

    public function cast(mixed $value): bool
    {
        $value = strtolower($value);

        return in_array($value, ['true', '1', 'yes', 'on'], true);
    }
}
