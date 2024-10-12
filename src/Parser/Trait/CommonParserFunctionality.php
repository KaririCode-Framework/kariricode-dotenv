<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Parser\Trait;

trait CommonParserFunctionality
{
    protected function isValidSetter(string $line): bool
    {
        $trimmedLine = trim($line);

        return !$this->isComment($trimmedLine) && $this->containsSetterChar($trimmedLine);
    }

    protected function isComment(string $line): bool
    {
        return 1 === preg_match('/^\s*#/', $line);
    }

    protected function containsSetterChar(string $line): bool
    {
        return str_contains($line, '=');
    }

    protected function parseEnvironmentVariable(string $line): array
    {
        $parts = explode('=', $line, 2);
        if (2 !== count($parts)) {
            return [null, null];
        }

        return [trim($parts[0]) ?: null, trim($parts[1])];
    }

    protected function sanitizeValue(string $value): string
    {
        $value = trim($value);

        return $this->removeQuotes($value);
    }

    protected function removeQuotes(string $value): string
    {
        if (strlen($value) > 1) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];
            if (('"' === $first && '"' === $last) || ("'" === $first && "'" === $last)) {
                return substr($value, 1, -1);
            }
        }

        return $value;
    }

    protected function isValidKey(?string $key): bool
    {
        return null !== $key && '' !== $key;
    }
}
