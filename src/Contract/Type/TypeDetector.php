<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Contract\Type;

interface TypeDetector
{
    public function detect(mixed $value): ?string;

    public function getPriority(): int;
}
