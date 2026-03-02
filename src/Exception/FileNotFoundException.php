<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Exception;

/**
 * Thrown when a required .env file does not exist or is not readable.
 *
 * @package KaririCode\Dotenv
 * @since   4.0.0 ARFA 1.3
 */
final class FileNotFoundException extends DotenvException
{
    public static function forPath(string $path): self
    {
        return new self("Environment file not found or not readable: {$path}");
    }
}
