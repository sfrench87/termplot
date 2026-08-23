<?php

declare(strict_types=1);

namespace Termplot\Tests\Unit\Render;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Termplot\Render\Bitmap;
use Termplot\Render\NullRenderer;
use Termplot\Exception\NotImplementedException;
use Termplot\Tests\Support\TinyPng;

#[CoversClass(Bitmap::class)]
#[CoversClass(NullRenderer::class)]
final class BitmapTest extends TestCase
{
    public function testPngFactory(): void
    {
        $bmp = Bitmap::png(TinyPng::bytes());
        $this->assertSame(Bitmap::FORMAT_PNG, $bmp->format);
        $this->assertNull($bmp->width);
        $this->assertSame(TinyPng::bytes(), $bmp->bytes);
    }

    public function testRgbSizeCheck(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Bitmap::rgb('xx', 2, 2);
    }

    public function testRgbaFactory(): void
    {
        $bmp = Bitmap::rgba(str_repeat("\0", 8), 2, 1);
        $this->assertSame(32, $bmp->format);
        $this->assertSame(2, $bmp->width);
        $this->assertSame(1, $bmp->height);
    }

    public function testNullRendererUnavailable(): void
    {
        $renderer = new NullRenderer();
        $this->assertFalse($renderer->isAvailable());
        $this->expectException(NotImplementedException::class);
        $renderer->line([1.0, 2.0], 800, 240);
    }
}
