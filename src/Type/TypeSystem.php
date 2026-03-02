<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type;

use KaririCode\Dotenv\Contract\TypeCaster;
use KaririCode\Dotenv\Contract\TypeDetector;
use KaririCode\Dotenv\Enum\ValueType;
use KaririCode\Dotenv\Type\Caster\ArrayCaster;
use KaririCode\Dotenv\Type\Caster\BooleanCaster;
use KaririCode\Dotenv\Type\Caster\FloatCaster;
use KaririCode\Dotenv\Type\Caster\IntegerCaster;
use KaririCode\Dotenv\Type\Caster\JsonCaster;
use KaririCode\Dotenv\Type\Caster\NullCaster;
use KaririCode\Dotenv\Type\Detector\ArrayDetector;
use KaririCode\Dotenv\Type\Detector\BooleanDetector;
use KaririCode\Dotenv\Type\Detector\FloatDetector;
use KaririCode\Dotenv\Type\Detector\IntegerDetector;
use KaririCode\Dotenv\Type\Detector\JsonDetector;
use KaririCode\Dotenv\Type\Detector\NullDetector;

/**
 * Orchestrates type detection and casting for environment variable values.
 *
 * Detectors are sorted by priority (descending) and evaluated in order.
 * The first detector that matches determines the ValueType, which selects
 * the corresponding caster. If no detector matches, the value remains a string.
 *
 * Both detectors and casters can be replaced or extended at runtime via
 * addDetector() and addCaster(), supporting the Open/Closed Principle.
 *
 * ARFA 1.3 P3: Adaptive Context — the type system adapts to application needs
 * by allowing custom detectors/casters without modifying core code.
 *
 * @package KaririCode\Dotenv
 * @since   4.0.0 ARFA 1.3
 */
final class TypeSystem
{
    /** @var list<TypeDetector> Sorted by priority descending. */
    private array $detectors = [];

    private bool $sorted = false;

    /** @var array<string, TypeCaster> Keyed by ValueType->name. */
    private array $casters = [];

    public function __construct()
    {
        $this->registerDefaults();
    }

    public function addDetector(TypeDetector $detector): void
    {
        $this->detectors[] = $detector;
        $this->sorted = false;
    }

    public function addCaster(ValueType $type, TypeCaster $caster): void
    {
        $this->casters[$type->name] = $caster;
    }

    public function detect(string $value): ValueType
    {
        $this->ensureSorted();

        foreach ($this->detectors as $detector) {
            $detected = $detector->detect($value);
            if ($detected !== null) {
                return $detected;
            }
        }

        return ValueType::String;
    }

    public function cast(string $value, ValueType $type): mixed
    {
        if ($type === ValueType::String) {
            return $value;
        }

        $caster = $this->casters[$type->name] ?? null;

        return $caster !== null ? $caster->cast($value) : $value;
    }

    public function resolve(string $value): mixed
    {
        $type = $this->detect($value);

        return $this->cast($value, $type);
    }

    // ── Internal ──────────────────────────────────────────────────────

    private function registerDefaults(): void
    {
        $this->detectors = [
            new NullDetector(),
            new BooleanDetector(),
            new IntegerDetector(),
            new FloatDetector(),
            new JsonDetector(),
            new ArrayDetector(),
        ];
        $this->sorted = false;

        $this->casters = [
            ValueType::Null->name => new NullCaster(),
            ValueType::Boolean->name => new BooleanCaster(),
            ValueType::Integer->name => new IntegerCaster(),
            ValueType::Float->name => new FloatCaster(),
            ValueType::Json->name => new JsonCaster(),
            ValueType::Array->name => new ArrayCaster(),
        ];
    }

    private function ensureSorted(): void
    {
        if ($this->sorted) {
            return;
        }

        usort($this->detectors, static fn (TypeDetector $a, TypeDetector $b): int => $b->priority() <=> $a->priority());
        $this->sorted = true;
    }
}
