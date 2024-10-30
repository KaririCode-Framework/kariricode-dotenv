<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Type\Caster;

use KaririCode\DataStructure\Collection\ArrayList;
use KaririCode\Dotenv\Contract\Type\TypeCaster;
use KaririCode\Dotenv\Contract\Type\TypeCasterRegistry;

class DotenvTypeCasterRegistry implements TypeCasterRegistry
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
        $this->register('array', new ArrayCaster());
        $this->register('json', new JsonCaster());
        $this->register('null', new NullCaster());
        $this->register('boolean', new BooleanCaster());
        $this->register('integer', new IntegerCaster());
        $this->register('float', new FloatCaster());
        $this->register('string', new StringCaster());
    }
}
