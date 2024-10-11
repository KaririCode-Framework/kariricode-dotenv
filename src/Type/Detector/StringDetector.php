<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Detector;

class StringDetector extends AbstractTypeDetector
{
    public const PRIORITY = 10;

    public function detect(mixed $value): ?string
    {
        return is_string($value) ? 'string' : null;
    }
}
