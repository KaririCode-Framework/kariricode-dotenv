<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Contract;

interface TypeCaster
{
    public function cast(mixed $value): mixed;
}
