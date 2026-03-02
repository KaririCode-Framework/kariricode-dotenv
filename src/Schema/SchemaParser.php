<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Schema;

use KaririCode\Dotenv\Exception\ValidationException;
use KaririCode\Dotenv\Validation\EnvironmentValidator;

/**
 * Parses `.env.schema` files and applies declarative validation rules.
 *
 * Schema format (INI-like):
 *
 * ```ini
 * [DB_HOST]
 * required = true
 * type = string
 * notEmpty = true
 *
 * [DB_PORT]
 * required = true
 * type = integer
 * min = 1
 * max = 65535
 * default = 5432
 *
 * [APP_ENV]
 * required = true
 * allowed = local, staging, production
 *
 * [API_KEY]
 * regex = /^[a-f0-9]{32}$/
 * ```
 *
 * Supported directives:
 * - `required = true|false`
 * - `type = string|integer|boolean|numeric|email|url`
 * - `notEmpty = true`
 * - `min = N` / `max = N` (numeric range, requires type=integer or type=numeric)
 * - `allowed = val1, val2, val3`
 * - `regex = /pattern/`
 * - `default = value` (informational — actual default injection is out of scope)
 *
 * @package KaririCode\Dotenv
 * @since   4.4.0
 */
final class SchemaParser
{
    /**
     * Parses a schema file and returns structured rules per variable.
     *
     * @return array<string, array<string, string>> Variable name → directive → value.
     */
    public function parse(string $content): array
    {
        $schema = [];
        $currentSection = null;

        foreach (explode("\n", $content) as $line) {
            $line = trim($line);

            // Skip empty lines and comments
            if ($line === '' || $line[0] === '#' || $line[0] === ';') {
                continue;
            }

            // Section header: [VAR_NAME]
            if (preg_match('/^\[([A-Za-z_][A-Za-z0-9_]*)\]$/', $line, $matches)) {
                $currentSection = $matches[1];
                $schema[$currentSection] ??= [];
                continue;
            }

            // Directive: key = value
            if ($currentSection !== null && str_contains($line, '=')) {
                $eqPos = strpos($line, '=');
                $key = trim(substr($line, 0, $eqPos));
                $value = trim(substr($line, $eqPos + 1));
                $schema[$currentSection][$key] = $value;
            }
        }

        return $schema;
    }

    /**
     * Applies parsed schema rules to an EnvironmentValidator.
     *
     * @param array<string, array<string, string>> $schema
     */
    public function applyToValidator(
        array $schema,
        EnvironmentValidator $validator,
    ): void {
        foreach ($schema as $variableName => $directives) {
            $isRequired = $this->boolDirective($directives, 'required');

            if ($isRequired) {
                $validator->required($variableName);
            } else {
                $validator->ifPresent($variableName);
            }

            // notEmpty
            if ($this->boolDirective($directives, 'notEmpty')) {
                $validator->notEmpty($variableName);
            }

            // Type-based rules
            $type = $directives['type'] ?? null;
            if ($type !== null) {
                match ($type) {
                    'integer' => $validator->isInteger($variableName),
                    'boolean' => $validator->isBoolean($variableName),
                    'numeric' => $validator->isNumeric($variableName),
                    'email' => $validator->email($variableName),
                    'url' => $validator->url($variableName),
                    'string' => null, // No additional rule needed
                    default => throw ValidationException::schemaViolation(
                        "Unknown type '{$type}' for [{$variableName}]."
                    ),
                };
            }

            // Numeric range (min/max)
            $min = isset($directives['min']) ? (float) $directives['min'] : null;
            $max = isset($directives['max']) ? (float) $directives['max'] : null;

            if ($min !== null && $max !== null) {
                $validator->between($min, $max);
            }

            // Allowed values
            if (isset($directives['allowed'])) {
                $allowed = array_map(trim(...), explode(',', $directives['allowed']));
                $validator->allowedValues($variableName, $allowed);
            }

            // Regex
            if (isset($directives['regex'])) {
                $validator->matchesRegex($variableName, $directives['regex']);
            }
        }
    }

    private function boolDirective(array $directives, string $key): bool
    {
        $value = $directives[$key] ?? 'false';

        return in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
    }
}
