<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Unit\Type\Detector\Trait;

use KaririCode\Dotenv\Type\Detector\Trait\ArrayParserTrait;
use KaririCode\Dotenv\Type\Detector\Trait\StringValidatorTrait;
use PHPUnit\Framework\TestCase;

final class ArrayParserTraitTest extends TestCase
{
    use ArrayParserTrait;
    use StringValidatorTrait;

    public function testExtractArrayElements(): void
    {
        $input = '[1, "two", 3.0]';
        $expected = ['1', 'two', '3.0'];
        $this->assertEquals($expected, $this->extractArrayElements($input));
    }

    public function testAllElementsMeet(): void
    {
        $elements = ['1', '2', '3'];
        $condition = function ($element) {
            return is_numeric($element);
        };
        $this->assertTrue($this->allElementsMeet($elements, $condition));

        $elements = ['1', 'two', '3'];
        $this->assertFalse($this->allElementsMeet($elements, $condition));
    }
}
