<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Contract;

interface Loader
{
    public function load(): string;
}
