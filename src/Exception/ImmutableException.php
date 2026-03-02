<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Exception;

/**
 * Thrown when attempting to overwrite a variable in Immutable load mode.
 *
 * ARFA 1.3 P1: Immutable State Transformation — once set, sealed.
 *
 * @package KaririCode\Dotenv
 * @since   4.0.0 ARFA 1.3
 */
final class ImmutableException extends DotenvException
{
    public static function alreadyDefined(string $name): self
    {
        return new self(
            "Environment variable '{$name}' is already defined and cannot be overwritten in Immutable mode."
        );
    }
}
