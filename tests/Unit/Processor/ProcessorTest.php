<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Unit\Processor;

use KaririCode\Dotenv\Processor\Base64DecodeProcessor;
use KaririCode\Dotenv\Processor\CsvToArrayProcessor;
use KaririCode\Dotenv\Processor\TrimProcessor;
use KaririCode\Dotenv\Processor\UrlNormalizerProcessor;
use PHPUnit\Framework\TestCase;

final class ProcessorTest extends TestCase
{
    // ── CsvToArrayProcessor ───────────────────────────────────────────

    public function testCsvToArraySplitsCommaDelimited(): void
    {
        $processor = new CsvToArrayProcessor();
        $raw = '192.168.1.1, 10.0.0.1, 172.16.0.1';

        self::assertSame(
            ['192.168.1.1', '10.0.0.1', '172.16.0.1'],
            $processor->process($raw, $raw),
        );
    }

    public function testCsvToArrayReturnsEmptyForBlank(): void
    {
        $processor = new CsvToArrayProcessor();

        self::assertSame([], $processor->process('', ''));
        self::assertSame([], $processor->process('   ', '   '));
    }

    public function testCsvToArrayWithCustomSeparator(): void
    {
        $processor = new CsvToArrayProcessor(separator: '|');
        $raw = 'a|b|c';

        self::assertSame(
            ['a', 'b', 'c'],
            $processor->process($raw, $raw),
        );
    }

    // ── Base64DecodeProcessor ─────────────────────────────────────────

    public function testBase64DecodeProcessesValidInput(): void
    {
        $processor = new Base64DecodeProcessor();
        $encoded = base64_encode('secret-key');

        self::assertSame('secret-key', $processor->process($encoded, $encoded));
    }

    public function testBase64DecodeThrowsOnInvalidInput(): void
    {
        $processor = new Base64DecodeProcessor();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid base64');

        $processor->process('!!!invalid!!!', '!!!invalid!!!');
    }

    // ── TrimProcessor ─────────────────────────────────────────────────

    public function testTrimRemovesWhitespace(): void
    {
        $processor = new TrimProcessor();
        $raw = "  hello\t\n";

        self::assertSame('hello', $processor->process($raw, $raw));
    }

    public function testTrimWithCustomCharacters(): void
    {
        $processor = new TrimProcessor(characters: '/');

        self::assertSame('path', $processor->process('/path/', '/path/'));
    }

    // ── UrlNormalizerProcessor ────────────────────────────────────────

    public function testUrlNormalizerAddsTrailingSlash(): void
    {
        $processor = new UrlNormalizerProcessor();
        $raw = 'https://api.example.com';

        self::assertSame('https://api.example.com/', $processor->process($raw, $raw));
    }

    public function testUrlNormalizerPreservesExistingSlash(): void
    {
        $processor = new UrlNormalizerProcessor();
        $raw = 'https://api.example.com/';

        self::assertSame('https://api.example.com/', $processor->process($raw, $raw));
    }

    public function testUrlNormalizerRemovesDoubleSlash(): void
    {
        $processor = new UrlNormalizerProcessor();
        $raw = 'https://api.example.com//';

        self::assertSame('https://api.example.com/', $processor->process($raw, $raw));
    }
}
