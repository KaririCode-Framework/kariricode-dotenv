<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Detector;

use KaririCode\Dotenv\Type\Detector\Trait\ArrayParserTrait;
use KaririCode\Dotenv\Type\Detector\Trait\StringValidatorTrait;

class JsonDetector extends AbstractTypeDetector
{
    use StringValidatorTrait;
    use ArrayParserTrait;

    public const PRIORITY = 90;

    public function detect(mixed $value): ?string
    {
        if (!$this->isStringInput($value)) {
            return null;
        }

        $cleanValue = $this->removeWhitespace($value);

        if ($this->isJsonObject($cleanValue)) {
            return 'json';
        }

        if ($this->isJsonArrayOfObjects($cleanValue)) {
            return 'json';
        }

        return null;
    }

    private function isJsonObject(string $value): bool
    {
        return $this->isObjectFormat($value) && $this->isValidJson($value);
    }

    private function isJsonArrayOfObjects(string $value): bool
    {
        if (!$this->isArrayFormat($value)) {
            return false;
        }

        $decoded = json_decode($value, true);
        if (JSON_ERROR_NONE !== json_last_error() || !is_array($decoded)) {
            return false;
        }

        if (empty($decoded)) {
            return true;
        }

        foreach ($decoded as $item) {
            if (!is_array($item) || $this->isSequentialArray($item)) {
                return false;
            }
        }

        return true;
    }

    private function isObjectFormat(string $value): bool
    {
        return $this->hasDelimiters($value, '{', '}');
    }

    private function isArrayFormat(string $value): bool
    {
        return $this->hasDelimiters($value, '[', ']');
    }

    private function isValidJson(string $value): bool
    {
        json_decode($value);

        return JSON_ERROR_NONE === json_last_error();
    }

    private function isSequentialArray(array $arr): bool
    {
        return array_keys($arr) === range(0, count($arr) - 1);
    }
}
