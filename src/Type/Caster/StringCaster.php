<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Caster;

use KaririCode\Dotenv\Contract\Type\TypeCaster;

class StringCaster implements TypeCaster
{
    public function canCast(mixed $value): bool
    {
        return !is_array($value) && (!is_object($value) || method_exists($value, '__toString'));
    }

    public function cast(mixed $value): string
    {
        if (is_null($value)) {
            return '';
        }

        return (string) $value;
    }
}
