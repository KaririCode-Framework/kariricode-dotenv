<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Detector\Trait;

trait ArrayParserTrait
{
    private function extractArrayElements(string $value): array
    {
        $elements = str_getcsv(substr($value, 1, -1));

        return array_map('trim', $elements);
    }

    private function allElementsMeet(array $elements, callable $condition): bool
    {
        foreach ($elements as $element) {
            if (!$condition($this->removeWhitespace($element))) {
                return false;
            }
        }

        return true;
    }
}
