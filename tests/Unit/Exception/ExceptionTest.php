<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Unit\Exception;

use KaririCode\Dotenv\Exception\DotenvException;
use KaririCode\Dotenv\Exception\FileNotFoundException;
use KaririCode\Dotenv\Exception\ImmutableException;
use KaririCode\Dotenv\Exception\ParseException;
use KaririCode\Dotenv\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

final class ExceptionTest extends TestCase
{
    // ── DotenvException ───────────────────────────────────────────────

    public function testDotenvExceptionExtendsRuntimeException(): void
    {
        $e = new class ('test') extends DotenvException {};
        self::assertInstanceOf(\RuntimeException::class, $e);
    }

    // ── FileNotFoundException ─────────────────────────────────────────

    public function testFileNotFoundExceptionForPath(): void
    {
        $e = FileNotFoundException::forPath('/var/www/.env');

        self::assertInstanceOf(DotenvException::class, $e);
        self::assertStringContainsString('/var/www/.env', $e->getMessage());
        self::assertStringContainsString('not found', $e->getMessage());
    }

    public function testFileNotFoundExceptionIsFinal(): void
    {
        $reflection = new \ReflectionClass(FileNotFoundException::class);
        self::assertTrue($reflection->isFinal());
    }

    // ── ImmutableException ────────────────────────────────────────────

    public function testImmutableExceptionAlreadyDefined(): void
    {
        $e = ImmutableException::alreadyDefined('DB_HOST');

        self::assertInstanceOf(DotenvException::class, $e);
        self::assertStringContainsString('DB_HOST', $e->getMessage());
        self::assertStringContainsString('Immutable', $e->getMessage());
    }

    public function testImmutableExceptionIsFinal(): void
    {
        $reflection = new \ReflectionClass(ImmutableException::class);
        self::assertTrue($reflection->isFinal());
    }

    // ── ParseException ────────────────────────────────────────────────

    public function testParseExceptionInvalidLine(): void
    {
        $e = ParseException::invalidLine('malformed line', 42, '/app/.env');

        self::assertInstanceOf(DotenvException::class, $e);
        self::assertStringContainsString('42', $e->getMessage());
        self::assertStringContainsString('/app/.env', $e->getMessage());
        self::assertStringContainsString('malformed line', $e->getMessage());
    }

    public function testParseExceptionInvalidVariableNameStrict(): void
    {
        $e = ParseException::invalidVariableName('myVar', 5, '/app/.env', strict: true);

        self::assertStringContainsString('myVar', $e->getMessage());
        self::assertStringContainsString('uppercase', $e->getMessage());
    }

    public function testParseExceptionInvalidVariableNameNonStrict(): void
    {
        $e = ParseException::invalidVariableName('invalid!', 5, '/app/.env', strict: false);

        self::assertStringContainsString('invalid!', $e->getMessage());
        self::assertStringContainsString('letters', $e->getMessage());
    }

    public function testParseExceptionUnterminatedQuote(): void
    {
        $e = ParseException::unterminatedQuote(10, '/app/.env');

        self::assertStringContainsString('10', $e->getMessage());
        self::assertStringContainsString('/app/.env', $e->getMessage());
        self::assertStringContainsString('Unterminated', $e->getMessage());
    }

    public function testParseExceptionCircularReference(): void
    {
        $e = ParseException::circularReference('SELF_REF', '/app/.env');

        self::assertStringContainsString('SELF_REF', $e->getMessage());
        self::assertStringContainsString('Circular', $e->getMessage());
    }

    public function testParseExceptionIsFinal(): void
    {
        $reflection = new \ReflectionClass(ParseException::class);
        self::assertTrue($reflection->isFinal());
    }

    // ── ValidationException ───────────────────────────────────────────

    public function testValidationExceptionMissingRequired(): void
    {
        $e = ValidationException::missingRequired(['DB_HOST', 'DB_PORT']);

        self::assertInstanceOf(DotenvException::class, $e);
        self::assertStringContainsString('DB_HOST', $e->getMessage());
        self::assertStringContainsString('DB_PORT', $e->getMessage());
        self::assertSame([], $e->errors()); // No batch errors
    }

    public function testValidationExceptionBatchErrors(): void
    {
        $errors = ['DB_HOST is required', 'DB_PORT must be integer', 'APP_ENV invalid'];
        $e = ValidationException::batchErrors($errors);

        self::assertSame($errors, $e->errors());
        self::assertCount(3, $e->errors());
        foreach ($errors as $error) {
            self::assertStringContainsString($error, $e->getMessage());
        }
    }

    public function testValidationExceptionSchemaViolation(): void
    {
        $e = ValidationException::schemaViolation('DB_PORT must be integer');

        self::assertStringContainsString('Schema', $e->getMessage());
        self::assertStringContainsString('DB_PORT', $e->getMessage());
    }

    public function testValidationExceptionErrorsDefaultToEmpty(): void
    {
        $e = ValidationException::missingRequired(['X']);
        self::assertSame([], $e->errors());
    }

    public function testValidationExceptionIsFinal(): void
    {
        $reflection = new \ReflectionClass(ValidationException::class);
        self::assertTrue($reflection->isFinal());
    }
}
