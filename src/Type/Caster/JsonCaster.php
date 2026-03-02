<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Caster;

use KaririCode\Dotenv\Contract\TypeCaster;

/**
 * Decodes a JSON object string into a PHP associative array.
 *
 * Throws JsonException on malformed input rather than returning false/null,
 * ensuring fail-fast behavior in production.
 *
 * @package KaririCode\Dotenv
 * @since   4.0.0 ARFA 1.3
 */
final readonly class JsonCaster implements TypeCaster
{
    /** @return array<string, mixed> */
    #[\Override]
    public function cast(string $value): array
    {
        $decoded = json_decode(trim($value), true, 512, JSON_THROW_ON_ERROR);

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }
}
