<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Caster;

use KaririCode\DataStructure\Collection\ArrayList;
use KaririCode\Dotenv\Contract\TypeCaster;

class TypeCasterRegistry
{
    private ArrayList $casters;

    public function __construct()
    {
        $this->casters = new ArrayList();
        $this->registerDefaultCasters();
    }

    public function register(string $type, TypeCaster $caster): void
    {
        $this->casters->set($type, $caster);
    }

    public function cast(string $type, mixed $value): mixed
    {
        $caster = $this->casters->get($type);

        if ($caster instanceof TypeCaster) {
            return $caster->cast($value);
        }

        return $value; // Fallback: return original value if no caster found
    }

    private function registerDefaultCasters(): void
    {
        $defaultCasters = [
            'array' => new ArrayCaster(),
            'json' => new JsonCaster(),
            'null' => new NullCaster(),
            'boolean' => new BooleanCaster(),
            'integer' => new IntegerCaster(),
            'float' => new FloatCaster(),
            'string' => new StringCaster(),
        ];

        foreach ($defaultCasters as $type => $caster) {
            $this->register($type, $caster);
        }
    }
}
