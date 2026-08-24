<?php

declare(strict_types=1);

namespace Termplot\Render\Gd;

use Termplot\Render\Bitmap;
use Termplot\Render\ChartRendererInterface;

/**
 * {@see ChartRendererInterface} adapter over {@see LineChart} / {@see BarChart}.
 */
final class GdRenderer implements ChartRendererInterface
{
    public function isAvailable(): bool
    {
        return LineChart::isAvailable();
    }

    public function line(array $series, int $width, int $height): Bitmap
    {
        return (new LineChart())->series('series', $series)->size($width, $height)->toBitmap();
    }

    public function bar(array $series, int $width, int $height): Bitmap
    {
        return (new BarChart())->series('series', $series)->size($width, $height)->toBitmap();
    }
}
