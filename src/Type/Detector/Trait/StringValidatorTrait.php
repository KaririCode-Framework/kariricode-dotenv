<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Detector\Trait;

trait StringValidatorTrait
{
    private function isStringInput(mixed $value): bool
    {
        return is_string($value);
    }

    private function removeWhitespace(string $value): string
    {
        return trim($value);
    }

    private function hasDelimiters(string $value, string $startDelimiter, string $endDelimiter): bool
    {
        return str_starts_with($value, $startDelimiter) && str_ends_with($value, $endDelimiter);
    }
}
