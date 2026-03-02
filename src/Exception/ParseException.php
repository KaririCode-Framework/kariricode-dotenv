<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Exception;

/**
 * Thrown when a .env line cannot be parsed into a valid KEY=VALUE pair.
 *
 * @package KaririCode\Dotenv
 * @since   4.0.0 ARFA 1.3
 */
final class ParseException extends DotenvException
{
    public static function invalidLine(string $line, int $lineNumber, string $filePath): self
    {
        return new self(
            "Invalid syntax at line {$lineNumber} in {$filePath}: {$line}"
        );
    }

    public static function invalidVariableName(string $name, int $lineNumber, string $filePath, bool $strict = false): self
    {
        $pattern = $strict
            ? '[A-Z][A-Z0-9_]* (uppercase letters, digits, underscores)'
            : '[A-Za-z_][A-Za-z0-9_.]* (letters, digits, underscores, dots)';

        return new self(
            "Invalid variable name '{$name}' at line {$lineNumber} in {$filePath}. "
            . "Names must match {$pattern}."
        );
    }

    public static function unterminatedQuote(int $lineNumber, string $filePath): self
    {
        return new self(
            "Unterminated quoted value at line {$lineNumber} in {$filePath}"
        );
    }

    public static function circularReference(string $variable, string $filePath): self
    {
        return new self(
            "Circular variable reference detected for '{$variable}' in {$filePath}"
        );
    }
}
