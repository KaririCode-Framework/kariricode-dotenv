<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Unit\Parser\Trait;

use KaririCode\Dotenv\Parser\Trait\CommonParserFunctionality;
use PHPUnit\Framework\TestCase;

final class CommonParserFunctionalityTest extends TestCase
{
    use CommonParserFunctionality;

    /**
     * @dataProvider validSetterProvider
     */
    public function testIsValidSetter(string $line, bool $expected): void
    {
        $this->assertEquals($expected, $this->isValidSetter($line));
    }

    public static function validSetterProvider(): array
    {
        return [
            'valid setter' => ['KEY=value', true],
            'setter with spaces' => [' KEY = value ', true],
            'comment' => ['# This is a comment', false],
            'empty line' => ['', false],
            'invalid line' => ['This is not a valid setter', false],
        ];
    }

    /**
     * @dataProvider commentProvider
     */
    public function testIsComment(string $line, bool $expected): void
    {
        $this->assertEquals($expected, $this->isComment($line));
    }

    public static function commentProvider(): array
    {
        return [
            'comment' => ['# This is a comment', true],
            'comment with leading space' => [' # This is a comment', true],
            'comment with multiple leading spaces' => ['   # This is a comment', true],
            'not a comment' => ['This is not a comment', false],
            'empty line' => ['', false],
            'line with only spaces' => ['   ', false],
        ];
    }

    /**
     * @dataProvider setterCharProvider
     */
    public function testContainsSetterChar(string $line, bool $expected): void
    {
        $this->assertEquals($expected, $this->containsSetterChar($line));
    }

    public static function setterCharProvider(): array
    {
        return [
            'contains setter char' => ['KEY=value', true],
            'multiple setter chars' => ['KEY=value=another', true],
            'no setter char' => ['This is a line without setter char', false],
            'empty line' => ['', false],
        ];
    }

    /**
     * @dataProvider parseEnvironmentVariableProvider
     */
    public function testParseEnvironmentVariable(string $line, array $expected): void
    {
        $this->assertEquals($expected, $this->parseEnvironmentVariable($line));
    }

    public static function parseEnvironmentVariableProvider(): array
    {
        return [
            'simple variable' => ['KEY=value', ['KEY', 'value']],
            'variable with spaces' => [' KEY = value ', ['KEY', 'value']],
            'variable with multiple equal signs' => ['KEY=value=another', ['KEY', 'value=another']],
            'empty value' => ['KEY=', ['KEY', '']],
            'invalid line' => ['This is not a valid variable', [null, null]],
        ];
    }

    /**
     * @dataProvider sanitizeValueProvider
     */
    public function testSanitizeValue(string $value, string $expected): void
    {
        $this->assertEquals($expected, $this->sanitizeValue($value));
    }

    public static function sanitizeValueProvider(): array
    {
        return [
            'unquoted value' => ['value', 'value'],
            'single quoted value' => ["'value'", 'value'],
            'double quoted value' => ['"value"', 'value'],
            'value with spaces' => [' value ', 'value'],
            'value with internal spaces' => [' value with spaces ', 'value with spaces'],
            'empty value' => ['', ''],
        ];
    }

    /**
     * @dataProvider removeQuotesProvider
     */
    public function testRemoveQuotes(string $value, string $expected): void
    {
        $this->assertEquals($expected, $this->removeQuotes($value));
    }

    public static function removeQuotesProvider(): array
    {
        return [
            'unquoted value' => ['value', 'value'],
            'single quoted value' => ["'value'", 'value'],
            'double quoted value' => ['"value"', 'value'],
            'mixed quotes' => ['"\'value\'"', "'value'"],
            'empty value' => ['', ''],
            'value with internal quotes' => ['"value \'quoted\' inside"', "value 'quoted' inside"],
        ];
    }

    /**
     * @dataProvider isValidKeyProvider
     */
    public function testIsValidKey(?string $key, bool $expected): void
    {
        $this->assertEquals($expected, $this->isValidKey($key));
    }

    public static function isValidKeyProvider(): array
    {
        return [
            'valid key' => ['KEY', true],
            'empty string' => ['', false],
            'null' => [null, false],
        ];
    }
}
