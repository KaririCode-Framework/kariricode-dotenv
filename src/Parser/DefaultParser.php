<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Parser;

use KaririCode\Dotenv\Contract\Parser;
use KaririCode\Dotenv\Parser\Trait\LineParsing;
use KaririCode\Dotenv\Parser\Trait\ValueInterpolation;

class DefaultParser implements Parser
{
    use LineParsing;
    use ValueInterpolation;

    public function parse(string $content): array
    {
        $lines = $this->splitLines($content);
        $parsedValues = [];

        foreach ($lines as $line) {
            if ($this->isValidSetter($line)) {
                [$key, $value] = $this->parseEnvironmentVariable($line);
                if (null !== $key && '' !== $key) {
                    $parsedValues[$key] = $value;
                }
            }
        }

        // Perform interpolation after all variables are parsed
        foreach ($parsedValues as $key => $value) {
            $parsedValues[$key] = $this->interpolateValue($value, $parsedValues);
        }

        return $parsedValues;
    }

    private function validateVariableName(?string $name): bool
    {
        return null !== $name && '' !== $name;
    }
}
