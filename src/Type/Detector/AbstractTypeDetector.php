<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Detector;

use KaririCode\Dotenv\Contract\TypeDetector;

abstract class AbstractTypeDetector implements TypeDetector
{
    public const PRIORITY = 0;

    public function getPriority(): int
    {
        return static::PRIORITY;
    }
}
