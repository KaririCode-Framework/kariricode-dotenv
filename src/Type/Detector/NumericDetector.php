<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Detector;

class NumericDetector extends AbstractTypeDetector
{
    public const PRIORITY = 70;

    public function detect(mixed $value): ?string
    {
        if (is_numeric($value)) {
            return false !== strpos($value, '.') ? 'float' : 'integer';
        }

        return null;
    }
}
