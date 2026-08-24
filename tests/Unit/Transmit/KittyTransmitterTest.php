<?php

declare(strict_types=1);

namespace Termplot\Tests\Unit\Transmit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Termplot\Protocol\DeleteAction;
use Termplot\Protocol\PlacementId;
use Termplot\Protocol\RecordingWriter;
use Termplot\Render\Bitmap;
use Termplot\Tests\Support\ApcParser;
use Termplot\Tests\Support\TinyPng;
use Termplot\Transmit\KittyTransmitter;
use Termplot\Transmit\TransmitOptions;

#[CoversClass(KittyTransmitter::class)]
#[CoversClass(TransmitOptions::class)]
final class KittyTransmitterTest extends TestCase
{
    public function testA4ReplaceSamePlacementDoesNotDeleteFirst(): void
    {
        $rec = new RecordingWriter();
        $tx = new KittyTransmitter($rec);
        $placement = new PlacementId(3, placementId: 1);
        $a = Bitmap::png(TinyPng::bytes());
        $b = Bitmap::rgb(str_repeat("\x11", 3), 1, 1);

        $tx->replace($a, $placement, z: -1, cursorMove: false);
        $tx->replace($b, $placement, z: -1, cursorMove: false);

        $frames = $rec->frames();
        $this->assertCount(2, $frames);
        foreach ($frames as $frame) {
            $keys = ApcParser::parse($frame)['keys'];
            $this->assertSame('T', $keys['a']);
            $this->assertSame('3', $keys['i']);
            $this->assertSame('1', $keys['p']);
            $this->assertSame('-1', $keys['z']);
            $this->assertArrayNotHasKey('d', $keys);
        }
        $this->assertStringNotContainsString('a=d', $rec->concatenated());
    }

    public function testTransmitPlaceDeleteQuery(): void
    {
        $rec = new RecordingWriter();
        $tx = new KittyTransmitter($rec);
        $placement = new PlacementId(8, placementId: 2);

        $tx->transmit(Bitmap::png(TinyPng::bytes()), $placement);
        $tx->place($placement);
        $tx->delete(DeleteAction::byImage(8, placementId: 2));
        $tx->query(8);

        $parsed = array_map(static fn (string $f) => ApcParser::parse($f)['keys']['a'], $rec->frames());
        $this->assertSame(['t', 'p', 'd', 'q'], $parsed);
    }

    public function testDefaultZIsNegative(): void
    {
        $opt = new TransmitOptions();
        $this->assertSame(-1, $opt->z);
        $this->assertFalse($opt->cursorMove);
        $this->assertSame(2, $opt->quiet);
    }
}
