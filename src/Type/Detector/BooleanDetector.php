<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Detector;

class BooleanDetector extends AbstractTypeDetector
{
    public const PRIORITY = 80;

    public function detect(mixed $value): ?string
    {
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_string($value)) {
            $lower = strtolower($value);
            if (in_array($lower, ['true', 'false', '1', '0', 'yes', 'no', 'on', 'off'], true)) {
                return 'boolean';
            }
        }

        return null;
    }
}
