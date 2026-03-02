<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Contract;

/**
 * Post-load transformer applied to environment variable values.
 * Processors run after type casting and can further transform the typed value.
 *
 * @package KaririCode\Dotenv
 * @since   4.4.0
 */
interface VariableProcessor
{
    public function process(string $rawValue, mixed $typedValue): mixed;
}
