<?php

declare(strict_types=1);

namespace Tests\Unit\Parser;

use KaririCode\Dotenv\Exception\InvalidValueException;
use KaririCode\Dotenv\Parser\StrictParser;
use PHPUnit\Framework\TestCase;

final class StrictParserTest extends TestCase
{
    private StrictParser $parser;

    protected function setUp(): void
    {
        $this->parser = new StrictParser();
    }

    /**
     * @dataProvider validInputProvider
     */
    public function testParseWithValidInput(string $input, array $expected): void
    {
        $result = $this->parser->parse($input);
        $this->assertEquals($expected, $result);
    }

    public static function validInputProvider(): array
    {
        return [
            'simple key-value' => [
                'KEY=value',
                ['KEY' => 'value'],
            ],
            'multiple lines' => [
                "KEY1=value1\nKEY2=value2",
                ['KEY1' => 'value1', 'KEY2' => 'value2'],
            ],
            'with comments' => [
                "KEY=value\n# This is a comment\nANOTHER_KEY=another_value",
                ['KEY' => 'value', 'ANOTHER_KEY' => 'another_value'],
            ],
            'with empty lines' => [
                "KEY=value\n\nANOTHER_KEY=another_value",
                ['KEY' => 'value', 'ANOTHER_KEY' => 'another_value'],
            ],
            'with interpolation' => [
                "BASE_DIR=/var/www\nAPP_DIR=\${BASE_DIR}/app",
                ['BASE_DIR' => '/var/www', 'APP_DIR' => '/var/www/app'],
            ],
            'with quotes' => [
                'QUOTED="This is a quoted value"',
                ['QUOTED' => 'This is a quoted value'],
            ],
        ];
    }

    public function testParseWithInvalidName(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Invalid character in variable name');

        $input = "VALID_KEY=value\nINVALID-KEY=value";
        $this->parser->parse($input);
    }

    public function testParseWithEmptyName(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Empty variable name');

        $input = '=value';
        $this->parser->parse($input);
    }

    public function testParseWithInvalidStartingCharacter(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Variable name must start with a letter or underscore');

        $input = '1INVALID=value';
        $this->parser->parse($input);
    }

    /**
     * @dataProvider invalidCharactersProvider
     */
    public function testParseWithInvalidCharacters(string $input): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Invalid character in variable name');

        $this->parser->parse($input);
    }

    public static function invalidCharactersProvider(): array
    {
        return [
            'with space' => ['INVALID KEY=value'],
            'with dash' => ['INVALID-KEY=value'],
            'with dot' => ['INVALID.KEY=value'],
            'with comma' => ['INVALID,KEY=value'],
            'with brackets' => ['INVALID[KEY]=value'],
        ];
    }

    public function testInterpolationWithUndefinedVariable(): void
    {
        $input = 'KEY=${UNDEFINED_VAR}';
        $result = $this->parser->parse($input);
        $this->assertEquals(['KEY' => '${UNDEFINED_VAR}'], $result);
    }

    public function testMultipleInterpolations(): void
    {
        $input = "BASE=/var\nAPP=/app\nPATH=\${BASE}\${APP}";
        $expected = ['BASE' => '/var', 'APP' => '/app', 'PATH' => '/var/app'];
        $result = $this->parser->parse($input);
        $this->assertEquals($expected, $result);
    }

    public function testParsingEmptyContent(): void
    {
        $input = '';
        $this->assertEquals([], $this->parser->parse($input));
    }

    public function testParsingOnlyComments(): void
    {
        $input = "# This is a comment\n# Another comment";
        $this->assertEquals([], $this->parser->parse($input));
    }
}
