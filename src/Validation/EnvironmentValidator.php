<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Validation;

use KaririCode\Dotenv\Contract\ValidationRule;
use KaririCode\Dotenv\Exception\ValidationException;
use KaririCode\Dotenv\Validation\Rule\AllowedValuesRule;
use KaririCode\Dotenv\Validation\Rule\BetweenRule;
use KaririCode\Dotenv\Validation\Rule\CustomRule;
use KaririCode\Dotenv\Validation\Rule\EmailRule;
use KaririCode\Dotenv\Validation\Rule\IsBooleanRule;
use KaririCode\Dotenv\Validation\Rule\IsIntegerRule;
use KaririCode\Dotenv\Validation\Rule\IsNumericRule;
use KaririCode\Dotenv\Validation\Rule\MatchesRegexRule;
use KaririCode\Dotenv\Validation\Rule\NotEmptyRule;
use KaririCode\Dotenv\Validation\Rule\UrlRule;

/**
 * Fluent validation DSL for environment variables.
 *
 * Collects all failures before throwing, unlike vlucas which stops at the first.
 * Supports conditional validation via ifPresent() and custom callbacks.
 *
 * ```php
 * $dotenv->validate()
 *     ->required('DB_HOST', 'DB_PORT')
 *     ->notEmpty('DB_HOST')
 *     ->isInteger('DB_PORT')->between(1, 65535)
 *     ->isBoolean('APP_DEBUG')
 *     ->allowedValues('APP_ENV', ['local', 'staging', 'production'])
 *     ->ifPresent('REDIS_HOST')->notEmpty()
 *     ->assert();
 * ```
 *
 * @package KaririCode\Dotenv
 * @since   4.1.0
 */
final class EnvironmentValidator
{
    /** @var array<string, list<ValidationRule>> Rules keyed by variable name. */
    private array $rules = [];

    /** @var list<string> Variables that must exist. */
    private array $requiredNames = [];

    /** @var list<string> Current target variable names for fluent chaining. */
    private array $currentTargets = [];

    /** @var bool When true, rules only apply if the variable exists. */
    private bool $conditionalMode = false;

    /**
     * @param \Closure(string): ?string $valueResolver Returns raw value or null if not set.
     */
    public function __construct(
        private readonly \Closure $valueResolver,
    ) {
    }

    // ── Targeting ─────────────────────────────────────────────────────

    /**
     * Declares variables that must exist (non-null). Does not apply rules to them
     * unless followed by chained rule methods.
     */
    public function required(string ...$names): self
    {
        $this->requiredNames = array_values(array_merge($this->requiredNames, $names));
        $this->currentTargets = array_values($names);
        $this->conditionalMode = false;

        return $this;
    }

    /**
     * Targets variables for rule application. If a variable is absent,
     * validation for it is silently skipped.
     */
    public function ifPresent(string ...$names): self
    {
        $this->currentTargets = array_values($names);
        $this->conditionalMode = true;

        return $this;
    }

    // ── Built-in Rules ────────────────────────────────────────────────

    public function notEmpty(string ...$names): self
    {
        return $this->applyRule(new NotEmptyRule(), array_values($names));
    }

    public function isInteger(string ...$names): self
    {
        return $this->applyRule(new IsIntegerRule(), array_values($names));
    }

    public function isBoolean(string ...$names): self
    {
        return $this->applyRule(new IsBooleanRule(), array_values($names));
    }

    public function isNumeric(string ...$names): self
    {
        return $this->applyRule(new IsNumericRule(), array_values($names));
    }

    /**
     * Applies a numeric range constraint. Requires prior isInteger() or isNumeric().
     * Always targets the current chain (set by required/ifPresent/isInteger/etc).
     */
    public function between(int|float $min, int|float $max): self
    {
        return $this->applyRule(new BetweenRule($min, $max), []);
    }

    /**
     * @param list<string> $allowed Accepted values for this variable.
     */
    public function allowedValues(string $name, array $allowed): self
    {
        $this->currentTargets = [$name];

        return $this->applyRule(new AllowedValuesRule($allowed), []);
    }

    public function matchesRegex(string $name, string $pattern): self
    {
        $this->currentTargets = [$name];

        return $this->applyRule(new MatchesRegexRule($pattern), []);
    }

    public function url(string ...$names): self
    {
        return $this->applyRule(new UrlRule(), array_values($names));
    }

    public function email(string ...$names): self
    {
        return $this->applyRule(new EmailRule(), array_values($names));
    }

    /**
     * @param \Closure(string): bool $callback
     */
    public function custom(string $name, \Closure $callback, string $message = ''): self
    {
        $this->currentTargets = [$name];
        $msg = $message !== '' ? $message : '{name} failed custom validation.';

        return $this->applyRule(new CustomRule($callback, $msg), []);
    }

    /**
     * Adds an arbitrary ValidationRule implementation to the current targets.
     */
    public function rule(ValidationRule $rule, string ...$names): self
    {
        return $this->applyRule($rule, array_values($names));
    }

    // ── Execution ─────────────────────────────────────────────────────

    /**
     * Runs all collected rules and throws with ALL failures if any exist.
     *
     * @throws ValidationException Containing every failure message.
     */
    public function assert(): void
    {
        $errors = [];

        // Check required presence first
        $missingRequired = [];
        foreach (array_unique($this->requiredNames) as $name) {
            $value = ($this->valueResolver)($name);
            if ($value === null) {
                $missingRequired[] = $name;
            }
        }

        if ($missingRequired !== []) {
            foreach ($missingRequired as $name) {
                $errors[] = "{$name} is required but not defined.";
            }
        }

        // Run rules per variable
        foreach ($this->rules as $name => $ruleList) {
            $value = ($this->valueResolver)($name);

            // Skip absent variables in conditional mode
            if ($value === null) {
                if ($this->isConditional($name)) {
                    continue;
                }

                // Already reported as missing-required above, don't run rules
                if (\in_array($name, $this->requiredNames, true)) {
                    continue;
                }

                // Not required, not conditional — skip silently
                continue;
            }

            foreach ($ruleList as $rule) {
                if (! $rule->passes($value)) {
                    $errors[] = str_replace('{name}', $name, $rule->message());
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::batchErrors($errors);
        }
    }

    // ── Internal ──────────────────────────────────────────────────────

    /** @var array<string, true> Variables registered via ifPresent(). */
    private array $conditionalNames = [];

    private function isConditional(string $name): bool
    {
        return isset($this->conditionalNames[$name]);
    }

    /**
     * @param list<string> $explicitNames Override current targets when non-empty.
     */
    private function applyRule(ValidationRule $rule, array $explicitNames): self
    {
        $targets = $explicitNames !== [] ? $explicitNames : $this->currentTargets;

        if ($explicitNames !== []) {
            $this->currentTargets = $explicitNames;
        }

        if ($this->conditionalMode) {
            foreach ($targets as $name) {
                $this->conditionalNames[$name] = true;
            }
        }

        foreach ($targets as $name) {
            $this->rules[$name][] = $rule;
        }

        return $this;
    }
}
