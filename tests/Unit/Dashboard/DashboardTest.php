<?php

declare(strict_types=1);

namespace Termplot\Tests\Unit\Dashboard;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Termplot\Dashboard\Dashboard;
use Termplot\Dashboard\Pane;
use Termplot\Probe\ImageIdAllocator;
use Termplot\Protocol\RecordingWriter;
use Termplot\Render\Bitmap;
use Termplot\Tests\Support\ApcParser;
use Termplot\Tests\Support\TinyPng;
use Termplot\Transmit\KittyTransmitter;

#[CoversClass(Dashboard::class)]
#[CoversClass(Pane::class)]
final class DashboardTest extends TestCase
{
    public function testA9TickEmitsTwoReplaceFramesWithDistinctImageIds(): void
    {
        $rec = new RecordingWriter();
        $dash = Dashboard::create(new KittyTransmitter($rec), new ImageIdAllocator())
            ->pane('throughput', cellRect: [0, 0, 60, 12])
            ->pane('latency', cellRect: [0, 13, 60, 10]);

        $panes = $dash->panes();
        $this->assertSame(1, $panes['throughput']->placement->imageId);
        $this->assertSame(2, $panes['latency']->placement->imageId);
        $this->assertLessThan(0, $panes['throughput']->z);
        $this->assertSame(-1, $panes['latency']->z);

        $bmpA = Bitmap::png(TinyPng::bytes());
        $bmpB = Bitmap::rgb(str_repeat("\x22", 3), 1, 1);
        $dash->tick(['throughput' => $bmpA, 'latency' => $bmpB]);

        $frames = $rec->frames();
        $this->assertCount(2, $frames);
        $first = ApcParser::parse($frames[0]);
        $second = ApcParser::parse($frames[1]);
        $this->assertSame('T', $first['keys']['a']);
        $this->assertSame('T', $second['keys']['a']);
        $this->assertSame('1', $first['keys']['i']);
        $this->assertSame('2', $second['keys']['i']);
        $this->assertSame('-1', $first['keys']['z']);
        $this->assertSame('-1', $second['keys']['z']);
        $this->assertStringNotContainsString('a=d', $rec->concatenated());
        $this->assertDoesNotMatchRegularExpression('/\x1b\[\d+;\d+H/', $rec->concatenated());
        foreach ($frames as $frame) {
            $this->assertStringStartsWith("\033_G", $frame);
        }
    }

    public function testUnknownPaneRejected(): void
    {
        $dash = Dashboard::create(new KittyTransmitter(new RecordingWriter()));
        $this->expectException(\InvalidArgumentException::class);
        $dash->tick(['missing' => Bitmap::png(TinyPng::bytes())]);
    }
}
