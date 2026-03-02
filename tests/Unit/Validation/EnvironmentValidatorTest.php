<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Unit\Validation;

use KaririCode\Dotenv\Exception\ValidationException;
use KaririCode\Dotenv\Validation\EnvironmentValidator;
use PHPUnit\Framework\TestCase;

final class EnvironmentValidatorTest extends TestCase
{
    private function makeValidator(array $variables): EnvironmentValidator
    {
        return new EnvironmentValidator(
            fn (string $name): ?string => $variables[$name] ?? null,
        );
    }

    // ── Required ──────────────────────────────────────────────────────

    public function testRequiredPassesWhenPresent(): void
    {
        $v = $this->makeValidator(['DB_HOST' => 'localhost']);
        $v->required('DB_HOST')->assert();
        $this->assertTrue(true);
    }

    public function testRequiredFailsWhenMissing(): void
    {
        $v = $this->makeValidator([]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('DB_HOST is required');

        $v->required('DB_HOST')->assert();
    }

    public function testMultipleRequiredReportsAll(): void
    {
        $v = $this->makeValidator(['DB_HOST' => 'localhost']);

        try {
            $v->required('DB_HOST', 'DB_PORT', 'DB_NAME')->assert();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertCount(2, $e->errors());
            $this->assertStringContainsString('DB_PORT', $e->getMessage());
            $this->assertStringContainsString('DB_NAME', $e->getMessage());
        }
    }

    // ── NotEmpty ──────────────────────────────────────────────────────

    public function testNotEmptyPasses(): void
    {
        $v = $this->makeValidator(['DB_HOST' => 'localhost']);
        $v->required('DB_HOST')->notEmpty()->assert();
        $this->assertTrue(true);
    }

    public function testNotEmptyFails(): void
    {
        $v = $this->makeValidator(['DB_HOST' => '   ']);

        $this->expectException(ValidationException::class);

        $v->required('DB_HOST')->notEmpty()->assert();
    }

    // ── IsInteger ─────────────────────────────────────────────────────

    public function testIsIntegerPasses(): void
    {
        $v = $this->makeValidator(['DB_PORT' => '5432']);
        $v->required('DB_PORT')->isInteger()->assert();
        $this->assertTrue(true);
    }

    public function testIsIntegerFailsOnFloat(): void
    {
        $v = $this->makeValidator(['DB_PORT' => '54.32']);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must be an integer');

        $v->required('DB_PORT')->isInteger()->assert();
    }

    public function testIsIntegerAcceptsSignedNumbers(): void
    {
        $v = $this->makeValidator(['OFFSET' => '-10']);
        $v->required('OFFSET')->isInteger()->assert();
        $this->assertTrue(true);
    }

    // ── Between ───────────────────────────────────────────────────────

    public function testBetweenPasses(): void
    {
        $v = $this->makeValidator(['PORT' => '8080']);
        $v->required('PORT')->isInteger()->between(1, 65535)->assert();
        $this->assertTrue(true);
    }

    public function testBetweenFailsBelowMin(): void
    {
        $v = $this->makeValidator(['PORT' => '0']);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('between 1 and 65535');

        $v->required('PORT')->isInteger()->between(1, 65535)->assert();
    }

    public function testBetweenFailsAboveMax(): void
    {
        $v = $this->makeValidator(['PORT' => '70000']);

        $this->expectException(ValidationException::class);

        $v->required('PORT')->isInteger()->between(1, 65535)->assert();
    }

    // ── IsBoolean ─────────────────────────────────────────────────────

    public function testIsBooleanPassesForAllVariants(): void
    {
        foreach (['true', 'false', '1', '0', 'yes', 'no', 'on', 'off', 'TRUE', 'False'] as $val) {
            $v = $this->makeValidator(['DEBUG' => $val]);
            $v->required('DEBUG')->isBoolean()->assert();
        }

        $this->assertTrue(true);
    }

    public function testIsBooleanFails(): void
    {
        $v = $this->makeValidator(['DEBUG' => 'maybe']);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must be a boolean');

        $v->required('DEBUG')->isBoolean()->assert();
    }

    // ── AllowedValues ─────────────────────────────────────────────────

    public function testAllowedValuesPasses(): void
    {
        $v = $this->makeValidator(['ENV' => 'production']);
        $v->allowedValues('ENV', ['local', 'staging', 'production'])->assert();
        $this->assertTrue(true);
    }

    public function testAllowedValuesFails(): void
    {
        $v = $this->makeValidator(['ENV' => 'invalid']);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must be one of');

        $v->allowedValues('ENV', ['local', 'staging', 'production'])->assert();
    }

    // ── MatchesRegex ──────────────────────────────────────────────────

    public function testMatchesRegexPasses(): void
    {
        $v = $this->makeValidator(['KEY' => 'abcdef0123456789abcdef0123456789']);
        $v->matchesRegex('KEY', '/\A[a-f0-9]{32}\z/')->assert();
        $this->assertTrue(true);
    }

    public function testMatchesRegexFails(): void
    {
        $v = $this->makeValidator(['KEY' => 'short']);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must match pattern');

        $v->matchesRegex('KEY', '/\A[a-f0-9]{32}\z/')->assert();
    }

    // ── URL ───────────────────────────────────────────────────────────

    public function testUrlPasses(): void
    {
        $v = $this->makeValidator(['APP_URL' => 'https://example.com']);
        $v->url('APP_URL')->assert();
        $this->assertTrue(true);
    }

    public function testUrlFails(): void
    {
        $v = $this->makeValidator(['APP_URL' => 'not-a-url']);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must be a valid URL');

        $v->url('APP_URL')->assert();
    }

    // ── Email ─────────────────────────────────────────────────────────

    public function testEmailPasses(): void
    {
        $v = $this->makeValidator(['ADMIN' => 'admin@example.com']);
        $v->email('ADMIN')->assert();
        $this->assertTrue(true);
    }

    public function testEmailFails(): void
    {
        $v = $this->makeValidator(['ADMIN' => 'not-an-email']);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must be a valid email');

        $v->email('ADMIN')->assert();
    }

    // ── Custom ────────────────────────────────────────────────────────

    public function testCustomCallbackPasses(): void
    {
        $v = $this->makeValidator(['DSN' => 'pgsql:host=localhost']);
        $v->custom('DSN', fn (string $v): bool => str_starts_with($v, 'pgsql:'))->assert();
        $this->assertTrue(true);
    }

    public function testCustomCallbackFails(): void
    {
        $v = $this->makeValidator(['DSN' => 'mysql:host=localhost']);

        $this->expectException(ValidationException::class);

        $v->custom('DSN', fn (string $v): bool => str_starts_with($v, 'pgsql:'), 'DSN must use pgsql driver.')->assert();
    }

    // ── IfPresent (conditional) ───────────────────────────────────────

    public function testIfPresentSkipsMissingVariable(): void
    {
        $v = $this->makeValidator([]);
        $v->ifPresent('REDIS_HOST')->notEmpty()->assert();
        $this->assertTrue(true);
    }

    public function testIfPresentValidatesWhenPresent(): void
    {
        $v = $this->makeValidator(['REDIS_HOST' => '']);

        $this->expectException(ValidationException::class);

        $v->ifPresent('REDIS_HOST')->notEmpty()->assert();
    }

    // ── Batch Errors ──────────────────────────────────────────────────

    public function testBatchCollectsAllErrors(): void
    {
        $v = $this->makeValidator([
            'PORT' => 'abc',
            'DEBUG' => 'maybe',
        ]);

        try {
            $v->required('PORT', 'DEBUG', 'MISSING')
                ->isInteger('PORT')
                ->isBoolean('DEBUG')
                ->assert();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            // MISSING is required, PORT is not integer, DEBUG is not boolean
            $this->assertCount(3, $e->errors());
        }
    }

    // ── IsNumeric ─────────────────────────────────────────────────────

    public function testIsNumericPasses(): void
    {
        foreach (['42', '3.14', '-0.5', '1e10'] as $val) {
            $v = $this->makeValidator(['N' => $val]);
            $v->required('N')->isNumeric()->assert();
        }

        $this->assertTrue(true);
    }

    public function testIsNumericFails(): void
    {
        $v = $this->makeValidator(['N' => 'abc']);

        $this->expectException(ValidationException::class);

        $v->required('N')->isNumeric()->assert();
    }
}
