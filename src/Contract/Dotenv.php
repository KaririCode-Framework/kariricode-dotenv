<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Contract;

interface Dotenv
{
    public function load(): void;

    public function addTypeDetector(TypeDetector $caster): self;

    public function addTypeCaster(string $type, TypeCaster $caster): self;
}
