<?php

declare(strict_types=1);

namespace Termplot\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Termplot\Fallback\BrailleSparkline;
use Termplot\Fallback\TableFallback;
use Termplot\Probe\Capability;
use Termplot\Probe\FakeProbe;
use Termplot\Protocol\RecordingWriter;
use Termplot\Render\Bitmap;
use Termplot\Render\NullRenderer;
use Termplot\Termplot;
use Termplot\Tests\Support\ApcParser;
use Termplot\Tests\Support\FakeBraille;
use Termplot\Tests\Support\FakeChartRenderer;
use Termplot\Tests\Support\TinyPng;
use Termplot\Transmit\KittyTransmitter;

#[CoversClass(Termplot::class)]
#[CoversClass(BrailleSparkline::class)]
#[CoversClass(TableFallback::class)]
final class TermplotFacadeTest extends TestCase
{
    protected function tearDown(): void
    {
        Termplot::reset();
    }

    public function testA11KittyPathUsesRendererAndReplace(): void
    {
        $rec = new RecordingWriter();
        Termplot::bind(
            probe: new FakeProbe(new Capability(true, 800, 600, 80, 24, true)),
            transmitter: new KittyTransmitter($rec),
            renderer: new FakeChartRenderer(Bitmap::png(TinyPng::bytes())),
        );

        Termplot::line([1, 2, 3])->width(800)->height(240)->draw();

        $this->assertNotEmpty($rec->frames());
        $keys = ApcParser::parse($rec->frames()[0])['keys'];
        $this->assertSame('T', $keys['a']);
        $this->assertSame('-1', $keys['z']);
    }

    public function testLadderFallsToBrailleWhenNotKitty(): void
    {
        $sink = fopen('php://memory', 'r+');
        $this->assertIsResource($sink);
        Termplot::bind(
            probe: new FakeProbe(new Capability(false, null, null, null, null, false)),
            braille: new FakeBraille('BRAILLE'),
            output: $sink,
        );

        Termplot::line([0.1, 0.5])->draw();
        rewind($sink);
        $this->assertSame('BRAILLE', stream_get_contents($sink));
        fclose($sink);
    }

    public function testA7NotKittyPathYieldsBrailleWithoutApc(): void
    {
        $sink = fopen('php://memory', 'r+');
        $this->assertIsResource($sink);
        Termplot::bind(
            probe: new FakeProbe(new Capability(false, null, null, null, null, false)),
            output: $sink,
        );

        Termplot::line([1, 2, 5, 3, 4])->width(800)->height(240)->draw();
        rewind($sink);
        $out = (string) stream_get_contents($sink);
        fclose($sink);

        $this->assertNotSame('', $out);
        $this->assertStringNotContainsString("\033_G", $out);
        $this->assertMatchesRegularExpression('/[\x{2800}-\x{28FF}]/u', $out);
    }

    public function testEmptyBrailleFallsThroughToTable(): void
    {
        $sink = fopen('php://memory', 'r+');
        $this->assertIsResource($sink);
        Termplot::bind(
            probe: new FakeProbe(new Capability(false, null, null, null, null, false)),
            braille: new FakeBraille(''),
            output: $sink,
        );

        Termplot::line([4, 8, 2])->draw();
        rewind($sink);
        $out = (string) stream_get_contents($sink);
        fclose($sink);

        $this->assertStringContainsString('idx', $out);
        $this->assertStringContainsString('4', $out);
        $this->assertStringNotContainsString("\033_G", $out);
    }

    public function testKittyWithoutRendererFallsBackToBraille(): void
    {
        $sink = fopen('php://memory', 'r+');
        $this->assertIsResource($sink);
        Termplot::bind(
            probe: new FakeProbe(new Capability(true, 800, 600, 80, 24, true)),
            renderer: new NullRenderer(),
            output: $sink,
        );

        Termplot::line([1, 2, 3])->draw();
        rewind($sink);
        $out = (string) stream_get_contents($sink);
        fclose($sink);

        $this->assertNotSame('', $out);
        $this->assertStringNotContainsString("\033_G", $out);
    }
}
