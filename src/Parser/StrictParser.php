<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Parser;

use KaririCode\Dotenv\Contract\Parser;
use KaririCode\Dotenv\Exception\InvalidValueException;
use KaririCode\Dotenv\Parser\Trait\LineParsing;
use KaririCode\Dotenv\Parser\Trait\ValueInterpolation;

class StrictParser implements Parser
{
    use LineParsing;
    use ValueInterpolation;

    private const INVALID_NAME_CHARS = '{}()[]=-+!@#$%^&*|\\,. ';

    public function parse(string $content): array
    {
        $lines = $this->splitLines($content);
        $parsedValues = [];

        foreach ($lines as $line) {
            if ($this->isValidSetter($line)) {
                [$key, $value] = $this->parseEnvironmentVariable($line);
                $this->validateVariableName($key);
                $parsedValues[$key] = $this->removeQuotes($value);
            }
        }

        // Perform interpolation after all variables are parsed
        foreach ($parsedValues as $key => $value) {
            $parsedValues[$key] = $this->interpolateValue($value, $parsedValues);
        }

        return $parsedValues;
    }

    private function validateVariableName(?string $name): void
    {
        if ('' === $name || null === $name) {
            throw new InvalidValueException('Empty variable name');
        }

        if ($this->containsInvalidCharacters($name)) {
            throw new InvalidValueException('Invalid character in variable name');
        }

        if (!$this->startsWithValidCharacter($name)) {
            throw new InvalidValueException('Variable name must start with a letter or underscore');
        }
    }

    private function containsInvalidCharacters(string $name): bool
    {
        $result = strpbrk($name, self::INVALID_NAME_CHARS);

        return false !== $result;
    }

    private function startsWithValidCharacter(string $name): bool
    {
        return 1 === preg_match('/^[a-zA-Z_]/', $name);
    }

    private function removeQuotes(string $value): string
    {
        return trim($value, '\'"');
    }
}
