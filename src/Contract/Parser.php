<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Contract;

interface Parser
{
    public function parse(string $content): array;
}
