<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Caster;

use KaririCode\Dotenv\Contract\Type\TypeCaster;

class ArrayCaster implements TypeCaster
{
    public function cast(mixed $value): array
    {
        if (is_string($value)) {
            $trimmed = trim($value, "[] \t\n\r\0\x0B");
            if ('' === $trimmed) {
                return [];
            }
            $items = explode(',', $trimmed);

            return array_map(fn ($item) => trim($item, " \t\n\r\0\x0B\"'"), $items);
        }

        return (array) $value;
    }
}
