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
        if (!file_exists($this->filePath)) {
            throw new InvalidFileException(sprintf('The environment file %s does not exist.', $this->filePath));
        }

        $contents = $this->getFileContents($this->filePath);

        if (false === $contents) {
            throw new InvalidFileException(sprintf('Unable to read the environment file at %s.', $this->filePath));
        }

        return $contents;
    }

    protected function getFileContents(string $filePath): string|false
    {
        return file_get_contents($filePath);
    }
}
