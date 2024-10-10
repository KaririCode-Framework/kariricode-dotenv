<?php

declare(strict_types=1);

namespace KaririCode\Dotenv;

use KaririCode\Dotenv\Loader\FileLoader;
use KaririCode\Dotenv\Parser\DefaultParser;

class DotenvFactory
{
    public static function create(string $path, bool $strict = false): Dotenv
    {
        $parser = new DefaultParser($strict);
        $loader = new FileLoader($path);

        return new Dotenv($parser, $loader);
    }
}
