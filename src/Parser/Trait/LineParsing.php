<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Parser\Trait;

trait LineParsing
{
    private const COMMENT_CHAR = '#';
    private const SETTER_CHAR = '=';

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
        $parts = explode(self::SETTER_CHAR, $line, 2);
        if (2 !== count($parts)) {
            return [null, null];
        }
        $name = trim($parts[0]);
        $value = trim($parts[1]);

        return ['' === $name ? null : $name, $value];
    }
}
