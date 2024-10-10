<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Loader;

use KaririCode\Dotenv\Contract\Loader;

class ArrayLoader implements Loader
{
    private array $variables;

    public function __construct(array $variables)
    {
        $this->variables = $variables;
    }

    public function load(): string
    {
        $output = '';
        foreach ($this->variables as $key => $value) {
            $output .= "$key=$value\n";
        }

        return $output;
    }
}
