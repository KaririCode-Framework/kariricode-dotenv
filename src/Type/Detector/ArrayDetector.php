<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Detector;

use KaririCode\Dotenv\Type\Detector\Trait\ArrayParserTrait;
use KaririCode\Dotenv\Type\Detector\Trait\StringValidatorTrait;

class ArrayDetector extends AbstractTypeDetector
{
    use StringValidatorTrait;
    use ArrayParserTrait;

    public const PRIORITY = 100;

    public function detect(mixed $value): ?string
    {
        if (!$this->isStringInput($value)) {
            return null;
        }

        $cleanValue = $this->removeWhitespace($value);

        if ($this->isArrayFormat($cleanValue)) {
            return 'array';
        }

        return null;
    }

    private function isArrayFormat(string $value): bool
    {
        return $this->hasDelimiters($value, '[', ']');
    }
}
