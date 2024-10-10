<?php

declare(strict_types=1);

namespace KaririCode\Dotenv;

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if (false === $value || null === $value) {
            return $default;
        }

        return $value;
    }
}
