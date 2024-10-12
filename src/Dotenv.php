<?php

declare(strict_types=1);

namespace KaririCode\Dotenv;

use KaririCode\Dotenv\Contract\Dotenv as DotenvContract;
use KaririCode\Dotenv\Contract\Loader;
use KaririCode\Dotenv\Contract\Parser;
use KaririCode\Dotenv\Contract\TypeCaster;
use KaririCode\Dotenv\Contract\TypeDetector;
use KaririCode\Dotenv\Type\TypeSystem;

class Dotenv implements DotenvContract
{
    public function __construct(
        private Parser $parser,
        private Loader $loader,
        private TypeSystem $typeSystem = new TypeSystem()
    ) {
    }

    public function load(): void
    {
        $content = $this->loader->load();
        $parsed = $this->parser->parse($content);

        if (!empty($parsed)) {
            foreach ($parsed as $key => $value) {
                $processedValue = $this->typeSystem->processValue($value);
                $this->setEnvironmentVariable($key, $processedValue);
            }
        }
    }

    public function addTypeDetector(TypeDetector $detector): self
    {
        $this->typeSystem->registerDetector($detector);

        return $this;
    }

    public function addTypeCaster(string $type, TypeCaster $caster): self
    {
        $this->typeSystem->registerCaster($type, $caster);

        return $this;
    }

    private function setEnvironmentVariable(string $key, mixed $value): void
    {
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
