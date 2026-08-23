<?php

declare(strict_types=1);

namespace Termplot\Tests\Unit\Protocol;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Termplot\Protocol\EscapeWriter;
use Termplot\Protocol\NullWriter;
use Termplot\Protocol\RecordingWriter;
use Termplot\Protocol\StreamWriter;

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
