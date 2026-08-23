<?php

declare(strict_types=1);

namespace Termplot\Tests\Unit\Render\Gd;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Termplot\Exception\RendererUnavailableException;
use Termplot\Render\Gd\BarChart;
use Termplot\Render\Gd\ChartPainter;
use Termplot\Render\Gd\GdRenderer;
use Termplot\Render\Gd\LineChart;

#[CoversClass(LineChart::class)]
#[CoversClass(BarChart::class)]
#[CoversClass(GdRenderer::class)]
#[CoversClass(ChartPainter::class)]
final class GdChartTest extends TestCase
{
    public function testIsAvailableTracksExtGd(): void
    {
        $this->assertSame(extension_loaded('gd'), LineChart::isAvailable());
        $this->assertSame(extension_loaded('gd'), BarChart::isAvailable());
        $this->assertSame(extension_loaded('gd'), (new GdRenderer())->isAvailable());
    }

    public function testToPngThrowsWhenGdMissing(): void
    {
        if (extension_loaded('gd')) {
            $this->markTestSkipped('ext-gd is loaded; unavailability path not exercised');
        }
        $this->expectException(RendererUnavailableException::class);
        (new LineChart())->series('qps', [1, 2, 3])->size(80, 40)->toPng();
    }

    #[RequiresPhpExtension('gd')]
    public function testLineChartPngHasSignatureAndSize(): void
    {
        $png = (new LineChart())->series('qps', [1, 3, 2, 5, 4])->size(320, 160)->toPng();
        $this->assertSame("\x89PNG", substr($png, 0, 4));
        $im = imagecreatefromstring($png);
        $this->assertNotFalse($im);
        $this->assertSame(320, imagesx($im));
        $this->assertSame(160, imagesy($im));
        imagedestroy($im);
    }

    #[RequiresPhpExtension('gd')]
    public function testBarChartPngHasSignatureAndSize(): void
    {
        $png = (new BarChart())->series('qps', [4, 8, 3, 6])->size(200, 100)->toPng();
        $this->assertSame("\x89PNG", substr($png, 0, 4));
        $im = imagecreatefromstring($png);
        $this->assertNotFalse($im);
        $this->assertSame(200, imagesx($im));
        $this->assertSame(100, imagesy($im));
        imagedestroy($im);
    }

    #[RequiresPhpExtension('gd')]
    public function testDifferentSeriesProduceDifferentPngs(): void
    {
        $a = (new LineChart())->series('a', [1, 2, 3, 4])->size(160, 80)->toPng();
        $b = (new LineChart())->series('a', [4, 3, 2, 1])->size(160, 80)->toPng();
        $this->assertNotSame($a, $b);
    }

    #[RequiresPhpExtension('gd')]
    public function testGdRendererReturnsPngBitmap(): void
    {
        $renderer = new GdRenderer();
        $this->assertTrue($renderer->isAvailable());
        $bmp = $renderer->line([1.0, 2.0, 1.5], 120, 60);
        $this->assertSame("\x89PNG", substr($bmp->bytes, 0, 4));
        $bar = $renderer->bar([3, 1, 4], 120, 60);
        $this->assertSame("\x89PNG", substr($bar->bytes, 0, 4));
    }
}
