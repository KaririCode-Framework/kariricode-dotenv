<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Loader;

use KaririCode\Dotenv\Contract\Loader;
use KaririCode\Dotenv\Exception\InvalidFileException;

class FileLoader implements Loader
{
    private string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function load(): string
    {
        if (!$this->isFileReadable($this->filePath)) {
            throw new InvalidFileException(sprintf('Unable to read the environment file at %s.', $this->filePath));
        }

        $contents = file_get_contents($this->filePath);

        if (false === $contents) {
            throw new InvalidFileException(sprintf('Unable to read the environment file at %s.', $this->filePath));
        }

        return $contents;
    }

    private function isFileReadable(string $filePath): bool
    {
        return is_readable($filePath) && is_file($filePath);
    }
}
