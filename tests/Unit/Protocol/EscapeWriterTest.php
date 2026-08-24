<?php

declare(strict_types=1);

namespace Termplot\Tests\Unit\Protocol;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Termplot\Protocol\EscapeWriter;
use Termplot\Protocol\NullWriter;
use Termplot\Protocol\RecordingWriter;
use Termplot\Protocol\StreamWriter;
use Termplot\Tests\Support\PartialWriteStream;
use Termplot\Tests\Support\ZeroWriteStream;

#[CoversClass(EscapeWriter::class)]
#[CoversClass(StreamWriter::class)]
#[CoversClass(RecordingWriter::class)]
#[CoversClass(NullWriter::class)]
final class EscapeWriterTest extends TestCase
{
    public function testStreamWriterWritesBytes(): void
    {
        $stream = fopen('php://memory', 'r+');
        $this->assertIsResource($stream);
        $writer = new StreamWriter($stream);
        $writer->write('abc');
        $writer->flush();
        rewind($stream);
        $this->assertSame('abc', stream_get_contents($stream));
        fclose($stream);
    }

    public function testWriteRetriesShortFwriteCounts(): void
    {
        PartialWriteStream::register();
        PartialWriteStream::reset();
        PartialWriteStream::$maxBytesPerWrite = 3;
        $stream = fopen('termplot-partial://frame', 'w');
        $this->assertIsResource($stream);
        $writer = new EscapeWriter($stream);
        $writer->write('abcdefghij');
        $writer->flush();
        fclose($stream);
        $this->assertSame('abcdefghij', PartialWriteStream::$buffers['frame']);
    }

    public function testWriteTreatsZeroByteFwriteAsError(): void
    {
        ZeroWriteStream::register();
        $stream = fopen('termplot-zero://x', 'w');
        $this->assertIsResource($stream);
        $writer = new EscapeWriter($stream);
        $this->expectException(\RuntimeException::class);
        $writer->write('nope');
    }

    public function testRecordingWriterCapturesFrames(): void
    {
        $rec = new RecordingWriter();
        $rec->write('one');
        $rec->write('two');
        $this->assertSame(['one', 'two'], $rec->frames());
        $this->assertSame('onetwo', $rec->concatenated());
        $rec->reset();
        $this->assertSame([], $rec->frames());
    }

    public function testNullWriterSwallows(): void
    {
        $null = new NullWriter();
        $null->write('x');
        $null->flush();
        $this->assertTrue(true);
    }
}
