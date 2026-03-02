<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Caster;

use KaririCode\Dotenv\Contract\TypeCaster;

/**
 * Decodes a JSON array string into a PHP list array.
 *
 * @package KaririCode\Dotenv
 * @since   4.0.0 ARFA 1.3
 */
final readonly class ArrayCaster implements TypeCaster
{
    /** @return list<mixed> */
    #[\Override]
    public function cast(string $value): array
    {
        $decoded = json_decode(trim($value), true, 512, JSON_THROW_ON_ERROR);

        return \is_array($decoded) ? array_values($decoded) : [];
    }
}
