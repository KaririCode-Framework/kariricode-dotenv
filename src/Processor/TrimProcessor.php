<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Processor;

use KaririCode\Dotenv\Contract\VariableProcessor;

final readonly class TrimProcessor implements VariableProcessor
{
    public function __construct(
        private string $characters = " \t\n\r\0\x0B",
    ) {
    }

    #[\Override]
    public function process(string $rawValue, mixed $typedValue): string
    {
        return trim($rawValue, $this->characters);
    }
}
