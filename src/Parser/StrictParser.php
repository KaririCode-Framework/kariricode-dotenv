<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Parser;

use KaririCode\Dotenv\Exception\InvalidValueException;
use KaririCode\Dotenv\Parser\Trait\CommonParserFunctionality;

class StrictParser extends AbstractParser
{
    use CommonParserFunctionality;

    private const INVALID_NAME_CHARS = '{}()[]=-+!@#$%^&*|\\,. ';

    public function parseLines(array $lines): array
    {
        $parsedValues = [];
        foreach ($lines as $line) {
            if ($this->isValidSetter($line)) {
                [$key, $value] = $this->parseEnvironmentVariable($line);
                $this->validateVariableName($key);
                $parsedValues[$key] = $this->sanitizeValue($value);
            }
        }

        return $parsedValues;
    }

    private function validateVariableName(?string $name): void
    {
        if (!$this->isValidKey($name)) {
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
        return false !== strpbrk($name, self::INVALID_NAME_CHARS);
    }

    private function startsWithValidCharacter(string $name): bool
    {
        return 1 === preg_match('/^[a-zA-Z_]/', $name);
    }
}
