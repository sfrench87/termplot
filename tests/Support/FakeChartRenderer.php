<?php

declare(strict_types=1);

namespace Termplot\Tests\Support;

use Termplot\Render\Bitmap;
use Termplot\Render\ChartRendererInterface;

final class FakeChartRenderer implements ChartRendererInterface
{
    public function __construct(private Bitmap $bitmap)
    {
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function line(array $series, int $width, int $height): Bitmap
    {
        return $this->bitmap;
    }

    public function bar(array $series, int $width, int $height): Bitmap
    {
        return $this->bitmap;
    }
}
