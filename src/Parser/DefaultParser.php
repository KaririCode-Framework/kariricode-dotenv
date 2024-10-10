<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Parser;

use KaririCode\Dotenv\Contract\Parser;
use KaririCode\Dotenv\Exception\InvalidValueException;

class DefaultParser implements Parser
{
    private const COMMENT_CHAR = '#';
    private const SETTER_CHAR = '=';
    private const INVALID_NAME_CHARS = '{}()[]';

    public function __construct(
        private readonly bool $strictMode = false
    ) {
    }

    public function parse(string $content): array
    {
        $lines = $this->splitLines($content);

        return array_reduce($lines, function (array $output, string $line) {
            if ($this->isValidSetter($line)) {
                [$key, $value] = $this->parseEnvironmentVariable($line);
                $output[$key] = $value;
            }

            return $output;
        }, []);
    }

    private function splitLines(string $content): array
    {
        return preg_split('/\r\n|\r|\n/', $content);
    }

    private function isValidSetter(string $line): bool
    {
        $trimmedLine = trim($line);

        return !$this->isComment($trimmedLine) && $this->containsSetterChar($trimmedLine);
    }

    private function isComment(string $line): bool
    {
        return str_starts_with($line, self::COMMENT_CHAR);
    }

    private function containsSetterChar(string $line): bool
    {
        return str_contains($line, self::SETTER_CHAR);
    }

    private function parseEnvironmentVariable(string $line): array
    {
        [$name, $value] = explode(self::SETTER_CHAR, $line, 2);
        $name = trim($name);
        $value = $this->interpolateValue(trim($value));

        $this->validateVariableName($name);

        return [$name, $value];
    }

    private function validateVariableName(string $name): void
    {
        match (true) {
            '' === $name => throw new InvalidValueException('Empty variable name'),
            $this->strictMode && $this->containsInvalidCharacters($name) => throw new InvalidValueException('Invalid character in variable name'),
            default => null,
        };
    }

    private function containsInvalidCharacters(string $name): bool
    {
        return false !== strpbrk($name, self::INVALID_NAME_CHARS);
    }

    private function interpolateValue(string $value): string
    {
        return preg_replace_callback(
            '/\$\{([A-Z0-9_]+)\}/',
            fn ($matches) => $_ENV[$matches[1]] ?? $matches[0],
            $value
        );
    }
}
