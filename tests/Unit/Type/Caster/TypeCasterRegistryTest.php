<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Caster;

use KaririCode\DataStructure\Collection\ArrayList;
use KaririCode\Dotenv\Contract\TypeCaster;

final class TypeCasterRegistry
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
        if (!$this->casters->has($type)) {
            return $value; // Return original value if no caster is registered for the type
        }

        $caster = $this->casters->get($type);

        return $caster->cast($value);
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
