<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Unit\Schema;

use KaririCode\Dotenv\Exception\ValidationException;
use KaririCode\Dotenv\Schema\SchemaParser;
use KaririCode\Dotenv\Validation\EnvironmentValidator;
use PHPUnit\Framework\TestCase;

final class SchemaParserTest extends TestCase
{
    private SchemaParser $parser;

    protected function setUp(): void
    {
        $this->parser = new SchemaParser();
    }

    // ── Parsing ───────────────────────────────────────────────────────

    public function testParsesBasicSchema(): void
    {
        $content = <<<'SCHEMA'
            [DB_HOST]
            required = true
            type = string
            notEmpty = true

            [DB_PORT]
            required = true
            type = integer
            min = 1
            max = 65535
            SCHEMA;

        $schema = $this->parser->parse($content);

        self::assertArrayHasKey('DB_HOST', $schema);
        self::assertArrayHasKey('DB_PORT', $schema);
        self::assertSame('true', $schema['DB_HOST']['required']);
        self::assertSame('string', $schema['DB_HOST']['type']);
        self::assertSame('true', $schema['DB_HOST']['notEmpty']);
        self::assertSame('integer', $schema['DB_PORT']['type']);
        self::assertSame('1', $schema['DB_PORT']['min']);
        self::assertSame('65535', $schema['DB_PORT']['max']);
    }

    public function testSkipsCommentsAndEmptyLines(): void
    {
        $content = <<<'SCHEMA'
            # This is a header comment
            ; INI-style comment

            [APP_ENV]
            required = true
            allowed = local, staging, production
            SCHEMA;

        $schema = $this->parser->parse($content);

        self::assertCount(1, $schema);
        self::assertArrayHasKey('APP_ENV', $schema);
    }

    public function testParsesAllowedValues(): void
    {
        $content = <<<'SCHEMA'
            [APP_ENV]
            allowed = local, staging, production
            SCHEMA;

        $schema = $this->parser->parse($content);

        self::assertSame('local, staging, production', $schema['APP_ENV']['allowed']);
    }

    public function testParsesRegexDirective(): void
    {
        $content = <<<'SCHEMA'
            [API_KEY]
            regex = /^[a-f0-9]{32}$/
            SCHEMA;

        $schema = $this->parser->parse($content);

        self::assertSame('/^[a-f0-9]{32}$/', $schema['API_KEY']['regex']);
    }

    // ── Validator Application ─────────────────────────────────────────

    public function testApplyToValidatorPassesWithValidData(): void
    {
        $schema = $this->parser->parse(<<<'SCHEMA'
            [DB_HOST]
            required = true
            notEmpty = true

            [DB_PORT]
            required = true
            type = integer
            min = 1
            max = 65535
            SCHEMA);

        $variables = ['DB_HOST' => 'localhost', 'DB_PORT' => '5432'];

        $validator = new EnvironmentValidator(
            fn (string $name): ?string => $variables[$name] ?? null,
        );

        $this->parser->applyToValidator($schema, $validator);
        $validator->assert();

        $this->assertTrue(true);
    }

    public function testApplyToValidatorFailsOnMissingRequired(): void
    {
        $schema = $this->parser->parse(<<<'SCHEMA'
            [DB_HOST]
            required = true
            SCHEMA);

        $validator = new EnvironmentValidator(
            fn (string $name): ?string => null,
        );

        $this->parser->applyToValidator($schema, $validator);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('DB_HOST');

        $validator->assert();
    }

    public function testApplyToValidatorFailsOnInvalidType(): void
    {
        $schema = $this->parser->parse(<<<'SCHEMA'
            [DB_PORT]
            required = true
            type = integer
            SCHEMA);

        $variables = ['DB_PORT' => 'not-a-number'];

        $validator = new EnvironmentValidator(
            fn (string $name): ?string => $variables[$name] ?? null,
        );

        $this->parser->applyToValidator($schema, $validator);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must be an integer');

        $validator->assert();
    }

    public function testApplyToValidatorWithAllowedValues(): void
    {
        $schema = $this->parser->parse(<<<'SCHEMA'
            [APP_ENV]
            required = true
            allowed = local, staging, production
            SCHEMA);

        $variables = ['APP_ENV' => 'invalid'];

        $validator = new EnvironmentValidator(
            fn (string $name): ?string => $variables[$name] ?? null,
        );

        $this->parser->applyToValidator($schema, $validator);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must be one of');

        $validator->assert();
    }

    public function testApplyToValidatorSkipsOptionalMissing(): void
    {
        $schema = $this->parser->parse(<<<'SCHEMA'
            [OPTIONAL_VAR]
            required = false
            type = integer
            SCHEMA);

        $validator = new EnvironmentValidator(
            fn (string $name): ?string => null,
        );

        $this->parser->applyToValidator($schema, $validator);
        $validator->assert();

        $this->assertTrue(true);
    }

    public function testUnknownTypeThrows(): void
    {
        $schema = $this->parser->parse(<<<'SCHEMA'
            [VAR]
            type = uuid
            SCHEMA);

        $validator = new EnvironmentValidator(
            fn (string $name): ?string => 'test',
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Unknown type 'uuid'");

        $this->parser->applyToValidator($schema, $validator);
    }
}
