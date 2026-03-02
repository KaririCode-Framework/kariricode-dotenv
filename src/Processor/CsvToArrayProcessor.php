<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Processor;

use KaririCode\Dotenv\Contract\VariableProcessor;

final readonly class CsvToArrayProcessor implements VariableProcessor
{
    public function __construct(
        private string $separator = ',',
    ) {
    }

    #[\Override]
    public function process(string $rawValue, mixed $typedValue): array
    {
        if (trim($rawValue) === '') {
            return [];
        }

        return array_map(trim(...), explode($this->separator, $rawValue));
    }
}
