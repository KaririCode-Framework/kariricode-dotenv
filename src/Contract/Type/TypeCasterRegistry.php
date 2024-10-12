<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Contract\Type;

interface TypeCasterRegistry
{
    public function register(string $type, TypeCaster $caster): void;

    public function cast(string $type, mixed $value): mixed;
}
