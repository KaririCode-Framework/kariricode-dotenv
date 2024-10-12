<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type;

use KaririCode\Dotenv\Contract\Type\TypeCaster;
use KaririCode\Dotenv\Contract\Type\TypeCasterRegistry;
use KaririCode\Dotenv\Contract\Type\TypeDetector;
use KaririCode\Dotenv\Contract\Type\TypeDetectorRegistry;
use KaririCode\Dotenv\Contract\Type\TypeSystem;
use KaririCode\Dotenv\Type\Caster\DotenvTypeCasterRegistry;
use KaririCode\Dotenv\Type\Detector\DotenvTypeDetectorRegistry;

class DotenvTypeSystem implements TypeSystem
{
    public function __construct(
        private TypeDetectorRegistry $detectorRegistry = new DotenvTypeDetectorRegistry(),
        private TypeCasterRegistry $casterRegistry = new DotenvTypeCasterRegistry()
    ) {
    }

    public function registerDetector(TypeDetector $detector): void
    {
        $this->detectorRegistry->registerDetector($detector);
    }

    public function registerCaster(string $type, TypeCaster $caster): void
    {
        $this->casterRegistry->register($type, $caster);
    }

    public function processValue(mixed $value): mixed
    {
        $detectedType = $this->detectorRegistry->detectType($value);

        return $this->casterRegistry->cast($detectedType, $value);
    }
}
