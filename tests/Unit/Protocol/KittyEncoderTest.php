<?php

declare(strict_types=1);

namespace Termplot\Tests\Unit\Protocol;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Termplot\Protocol\DeleteAction;
use Termplot\Protocol\KittyEncoder;
use Termplot\Protocol\PlacementId;
use Termplot\Render\Bitmap;
use Termplot\Tests\Support\ApcParser;
use Termplot\Tests\Support\TinyPng;
use Termplot\Transmit\TransmitOptions;

#[CoversClass(KittyEncoder::class)]
final class KittyEncoderTest extends TestCase
{
    public function testA2SmallPngEmitsSingleApcFrame(): void
    {
        $png = TinyPng::bytes();
        $this->assertLessThanOrEqual(1024, strlen($png));

        $frames = KittyEncoder::encode(
            Bitmap::png($png),
            new PlacementId(1, placementId: 1),
            new TransmitOptions(),
        );

        $this->assertCount(1, $frames);
        $parsed = ApcParser::parse($frames[0]);
        $this->assertSame('T', $parsed['keys']['a']);
        $this->assertSame('100', $parsed['keys']['f']);
        $this->assertSame('d', $parsed['keys']['t']);
        $this->assertSame('1', $parsed['keys']['i']);
        $this->assertSame('1', $parsed['keys']['p']);
        $this->assertSame('-1', $parsed['keys']['z']);
        $this->assertSame('1', $parsed['keys']['C']);
        $this->assertSame('2', $parsed['keys']['q']);
        $this->assertArrayNotHasKey('m', $parsed['keys']);
        $this->assertSame(base64_encode($png), $parsed['payload']);

        $expected = "\033_Ga=T,f=100,t=d,i=1,p=1,z=-1,C=1,q=2;" . base64_encode($png) . "\033\\";
        $this->assertSame($expected, $frames[0]);
    }

    public function testA3MultiChunkAt4096Boundary(): void
    {
        $bytes = str_repeat("\0", 3073);
        $frames = KittyEncoder::encode(
            Bitmap::png($bytes),
            new PlacementId(4, placementId: 2),
            new TransmitOptions(),
        );

        $b64 = base64_encode($bytes);
        $this->assertGreaterThan(4096, strlen($b64));
        $this->assertCount(2, $frames);

        $first = ApcParser::parse($frames[0]);
        $last = ApcParser::parse($frames[1]);

        $this->assertSame('1', $first['keys']['m']);
        $this->assertSame('T', $first['keys']['a']);
        $this->assertSame('4', $first['keys']['i']);
        $this->assertSame(4096, strlen($first['payload']));
        $this->assertSame(0, strlen($first['payload']) % 4);

        $this->assertSame('0', $last['keys']['m']);
        $this->assertSame('2', $last['keys']['q']);
        $this->assertCount(2, $last['keys']);
        $this->assertSame($b64, $first['payload'] . $last['payload']);
    }

    public function testRgbIncludesWidthAndHeight(): void
    {
        $frames = KittyEncoder::encode(
            Bitmap::rgb(str_repeat("\xFF", 12), 2, 2),
            new PlacementId(1),
            new TransmitOptions(),
        );
        $keys = ApcParser::parse($frames[0])['keys'];
        $this->assertSame('24', $keys['f']);
        $this->assertSame('2', $keys['s']);
        $this->assertSame('2', $keys['v']);
        $this->assertArrayNotHasKey('p', $keys);
    }

    public function testOptionalCompressionAndTransientKeys(): void
    {
        $frames = KittyEncoder::encode(
            Bitmap::png(TinyPng::bytes()),
            new PlacementId(9, placementId: 3),
            new TransmitOptions(compress: true, transient: true),
        );
        $keys = ApcParser::parse($frames[0])['keys'];
        $this->assertSame('z', $keys['o']);
        $this->assertSame('1', $keys['N']);
    }

    public function testDeleteFrame(): void
    {
        $frames = KittyEncoder::encodeDelete(DeleteAction::byImage(10, placementId: 7));
        $this->assertCount(1, $frames);
        $this->assertSame(
            "\033_Ga=d,d=i,i=10,p=7\033\\",
            $frames[0],
        );
    }

    public function testQueryFrame(): void
    {
        $frames = KittyEncoder::encodeQuery(31);
        $this->assertSame(
            "\033_Ga=q,f=24,t=d,s=1,v=1,i=31;AAAA\033\\",
            $frames[0],
        );
        $this->assertSame("\033[c", KittyEncoder::PRIMARY_DA);
    }

    public function testPlaceFrame(): void
    {
        $frames = KittyEncoder::encodePlace(
            new PlacementId(5, placementId: 1),
            new TransmitOptions(action: TransmitOptions::ACTION_PLACE, columns: 60, rows: 12),
        );
        $keys = ApcParser::parse($frames[0])['keys'];
        $this->assertSame('p', $keys['a']);
        $this->assertSame('60', $keys['c']);
        $this->assertSame('12', $keys['r']);
        $this->assertSame('-1', $keys['z']);
        $this->assertSame('1', $keys['C']);
    }
}
