<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Caster;

use KaririCode\Dotenv\Contract\TypeCaster;

class JsonCaster implements TypeCaster
{
    /**
     * @throws \JsonException
     */
    public function cast(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $trimmedValue = $this->removeSurroundingQuotes($value);

        return $this->decodeJson($trimmedValue);
    }

    private function removeSurroundingQuotes(string $value): string
    {
        return trim($value, '"\'');
    }

    /**
     * @throws \JsonException
     */
    private function decodeJson(string $json): mixed
    {
        try {
            return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return $json;
        }
    }
}
