<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Detector;

class ArrayDetector extends AbstractTypeDetector
{
    public const PRIORITY = 100;

    public function detect(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $trimmed = trim($value);
        if (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']')) {
            $items = str_getcsv(substr($trimmed, 1, -1));
            foreach ($items as $item) {
                if ($this->containsNestedStructure(trim($item))) {
                    return null;
                }
            }

            return 'array';
        }

        return null;
    }

    private function containsNestedStructure(string $item): bool
    {
        return (str_starts_with($item, '{') && str_ends_with($item, '}'))
               || (str_starts_with($item, '[') && str_ends_with($item, ']'));
    }
}
