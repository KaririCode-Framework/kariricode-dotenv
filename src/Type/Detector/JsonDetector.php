<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Detector;

class JsonDetector extends AbstractTypeDetector
{
    public const PRIORITY = 90;

    public function detect(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $trimmed = trim($value);
        if ((str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}'))
            || (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']'))) {
            json_decode($trimmed);

            return JSON_ERROR_NONE === json_last_error() ? 'json' : null;
        }

        return null;
    }
}
