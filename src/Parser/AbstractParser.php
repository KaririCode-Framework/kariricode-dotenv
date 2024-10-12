<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Parser;

use KaririCode\Dotenv\Contract\Parser;

abstract class AbstractParser implements Parser
{
    public function parse(string $content): array
    {
        $lines = $this->splitLines($content);
        $parsedValues = $this->parseLines($lines);

        return $this->interpolateValues($parsedValues);
    }

    abstract protected function parseLines(array $lines): array;

    protected function splitLines(string $content): array
    {
        return preg_split('/\r\n|\r|\n/', $content);
    }

    protected function interpolateValues(array $parsedValues): array
    {
        return array_map(
            fn ($value) => $this->interpolateValue($value, $parsedValues),
            $parsedValues
        );
    }

    protected function interpolateValue(string $value, array $parsedValues): string
    {
        return preg_replace_callback(
            '/\${([A-Z0-9_]+)}/',
            fn ($matches) => $parsedValues[$matches[1]] ?? $matches[0],
            $value
        );
    }
}
