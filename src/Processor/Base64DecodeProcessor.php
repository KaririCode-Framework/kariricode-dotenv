<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Processor;

use KaririCode\Dotenv\Contract\VariableProcessor;

final readonly class Base64DecodeProcessor implements VariableProcessor
{
    public function process(string $rawValue, mixed $typedValue): string
    {
        $decoded = base64_decode($rawValue, true);

        if ($decoded === false) {
            throw new \RuntimeException("Invalid base64 value: cannot decode.");
        }

        return $decoded;
    }
}
