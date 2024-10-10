<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Detector;

use KaririCode\DataStructure\Collection\ArrayList;
use KaririCode\Dotenv\Contract\TypeDetector;

class TypeDetectorRegistry
{
    public function __construct(
        private $detectors = new ArrayList()
    ) {
        $this->registerDefaultDetectors();
    }

    public function registerDetector(TypeDetector $detector): void
    {
        $this->detectors->add($detector);
        $this->sortDetectors();
    }

    public function detectType(mixed $value): string
    {
        foreach ($this->detectors->getItems() as $detector) {
            if ($type = $detector->detect($value)) {
                return $type;
            }
        }

        return 'string'; // Fallback
    }

    private function registerDefaultDetectors(): void
    {
        $defaultDetectors = [
            new ArrayDetector(),
            new JsonDetector(),
            new NullDetector(),
            new BooleanDetector(),
            new NumericDetector(),
            new StringDetector(),
        ];

        foreach ($defaultDetectors as $detector) {
            $this->registerDetector($detector);
        }
    }

    private function sortDetectors(): void
    {
        $detectors = $this->detectors->getItems();
        usort($detectors, fn (TypeDetector $a, TypeDetector $b) => $b->getPriority() - $a->getPriority());
        $this->detectors->clear();
        foreach ($detectors as $detector) {
            $this->detectors->add($detector);
        }
    }
}
