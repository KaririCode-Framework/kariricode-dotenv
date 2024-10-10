<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Detector;

class NullDetector extends AbstractTypeDetector
{
    public const PRIORITY = 95;

    public function detect(mixed $value): ?string
    {
        if (null === $value || 'null' === strtolower($value) || '' === $value) {
            return 'null';
        }

        return null;
    }
}
