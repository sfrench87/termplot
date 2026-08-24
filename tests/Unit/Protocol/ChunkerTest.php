<?php

declare(strict_types=1);

namespace Termplot\Tests\Unit\Protocol;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Termplot\Protocol\Chunker;

#[CoversClass(Chunker::class)]
final class ChunkerTest extends TestCase
{
    public function testEmptyPayloadIsSingleEmptyChunk(): void
    {
        $this->assertSame([''], Chunker::split(''));
    }

    public function testDoesNotSplitAtExactly4096(): void
    {
        $payload = str_repeat('A', 4096);
        $chunks = Chunker::split($payload);
        $this->assertCount(1, $chunks);
        $this->assertSame($payload, $chunks[0]);
    }

    public function testSplitsAbove4096OnFourByteBoundary(): void
    {
        $payload = str_repeat('B', 4097);
        $chunks = Chunker::split($payload);
        $this->assertCount(2, $chunks);
        $this->assertSame(4096, strlen($chunks[0]));
        $this->assertSame(1, strlen($chunks[1]));
        $this->assertSame(0, strlen($chunks[0]) % 4);
        $this->assertSame($payload, implode('', $chunks));
    }

    public function testRejectsNonMultipleOfFourSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Chunker::split('abcd', 6);
    }
}
