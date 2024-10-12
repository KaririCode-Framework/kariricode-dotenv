<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Parser;

use KaririCode\Dotenv\Parser\Trait\CommonParserFunctionality;

class DefaultParser extends AbstractParser
{
    use CommonParserFunctionality;

    public function parseLines(array $lines): array
    {
        $parsedValues = [];
        foreach ($lines as $line) {
            if ($this->isValidSetter($line)) {
                [$key, $value] = $this->parseEnvironmentVariable($line);
                if ($this->isValidKey($key)) {
                    $parsedValues[$key] = $this->sanitizeValue($value);
                }
            }
        }

        return $parsedValues;
    }
}
