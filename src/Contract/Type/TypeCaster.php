<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Contract\Type;

interface TypeCaster
{
    public function cast(mixed $value): mixed;
}
