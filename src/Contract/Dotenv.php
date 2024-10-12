<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Contract;

use KaririCode\Dotenv\Contract\Type\TypeCaster;
use KaririCode\Dotenv\Contract\Type\TypeDetector;

interface Dotenv
{
    public function load(): void;

    public function addTypeDetector(TypeDetector $caster): self;

    public function addTypeCaster(string $type, TypeCaster $caster): self;
}
