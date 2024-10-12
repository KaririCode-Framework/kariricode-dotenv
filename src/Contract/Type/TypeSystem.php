<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Contract\Type;

interface TypeSystem
{
    public function registerDetector(TypeDetector $detector): void;

    public function registerCaster(string $type, TypeCaster $caster): void;

    public function processValue(mixed $value): mixed;
}
