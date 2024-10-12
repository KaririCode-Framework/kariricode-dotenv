<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Contract\Type;

interface TypeDetectorRegistry
{
    public function registerDetector(TypeDetector $detector): void;

    public function detectType(mixed $value): string;
}
