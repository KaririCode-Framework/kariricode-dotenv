<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Caster;

use KaririCode\Dotenv\Contract\TypeCaster;

class NullCaster implements TypeCaster
{
    public function canCast(mixed $value): bool
    {
        return 'null' === $value || '' === $value;
    }

    public function cast(mixed $value): ?string
    {
        return null;
    }
}
