<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Unit\Type\Detector\Trait;

use KaririCode\Dotenv\Type\Detector\Trait\StringValidatorTrait;
use PHPUnit\Framework\TestCase;

final class StringValidatorTraitTest extends TestCase
{
    use StringValidatorTrait;

    public function testIsStringInput(): void
    {
        $this->assertTrue($this->isStringInput('test'));
        $this->assertFalse($this->isStringInput(123));
        $this->assertFalse($this->isStringInput([]));
    }

    public function testRemoveWhitespace(): void
    {
        $this->assertEquals('test', $this->removeWhitespace(' test '));
        $this->assertEquals('test', $this->removeWhitespace("\ttest\n"));
    }

    public function testHasDelimiters(): void
    {
        $this->assertTrue($this->hasDelimiters('[test]', '[', ']'));
        $this->assertFalse($this->hasDelimiters('test', '[', ']'));
        $this->assertFalse($this->hasDelimiters('[test', '[', ']'));
        $this->assertFalse($this->hasDelimiters('test]', '[', ']'));
    }
}
