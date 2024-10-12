<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Parser\Trait;

trait ValueInterpolation
{
    private function interpolateValue(string $value, array $parsedValues): string
    {
        return preg_replace_callback('/\${([A-Z0-9_]+)}/', function ($matches) use ($parsedValues) {
            $varName = $matches[1];

            return $parsedValues[$varName] ?? $matches[0];
        }, $value);
    }
}
