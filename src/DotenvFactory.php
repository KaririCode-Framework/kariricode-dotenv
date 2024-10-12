<?php

declare(strict_types=1);

namespace KaririCode\Dotenv;

use KaririCode\Dotenv\Loader\FileLoader;
use KaririCode\Dotenv\Parser\DefaultParser;
use KaririCode\Dotenv\Parser\StrictParser;

class DotenvFactory
{
    public static function create(string $path, bool $strict = false): Dotenv
    {
        $parser = $strict ? new StrictParser() : new DefaultParser();
        $loader = new FileLoader($path);

        return new Dotenv($parser, $loader);
    }
}
