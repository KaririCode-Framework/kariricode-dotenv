<?php

declare(strict_types=1);

namespace Tests\Unit\Loader;

use KaririCode\Dotenv\Loader\ArrayLoader;
use PHPUnit\Framework\TestCase;

final class ArrayLoaderTest extends TestCase
{
    public function testLoad(): void
    {
        $variables = [
            'KEY1' => 'value1',
            'KEY2' => 'value2',
        ];

        $loader = new ArrayLoader($variables);
        $result = $loader->load();

        $expected = "KEY1=value1\nKEY2=value2\n";
        $this->assertEquals($expected, $result);
    }

    public function testLoadWithEmptyArray(): void
    {
        $loader = new ArrayLoader([]);
        $result = $loader->load();

        $this->assertEquals('', $result);
    }
}
