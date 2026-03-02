<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Processor;

use KaririCode\Dotenv\Contract\VariableProcessor;

final readonly class UrlNormalizerProcessor implements VariableProcessor
{
    public function process(string $rawValue, mixed $typedValue): string
    {
        return rtrim($rawValue, '/') . '/';
    }
}
