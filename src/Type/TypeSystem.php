<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type;

use KaririCode\Dotenv\Contract\TypeCaster;
use KaririCode\Dotenv\Contract\TypeDetector;
use KaririCode\Dotenv\Type\Caster\TypeCasterRegistry;
use KaririCode\Dotenv\Type\Detector\TypeDetectorRegistry;

class TypeSystem
{
    public function __construct(
        private $detectorRegistry = new TypeDetectorRegistry(),
        private $casterRegistry = new TypeCasterRegistry()
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
