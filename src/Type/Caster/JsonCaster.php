<?php

declare(strict_types=1);

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Caster;

use KaririCode\Dotenv\Contract\TypeCaster;

class JsonCaster implements TypeCaster
{
    public function canCast(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $trimmed = trim($value);

        return (str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}'))
               || (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']'));
    }

    public function cast(mixed $value): mixed
    {
        return json_encode($value);
    }
}
