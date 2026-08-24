<?php

declare(strict_types=1);

namespace Termplot\Render;

use Termplot\Exception\NotImplementedException;

/**
 * Unavailable renderer for tests and hosts without a chart backend.
 * Production defaults to {@see \Termplot\Render\Gd\GdRenderer}.
 */
final class NullRenderer implements ChartRendererInterface
{
    public function isAvailable(): bool
    {
        return false;
    }

    public function line(array $series, int $width, int $height): Bitmap
    {
        throw new NotImplementedException(
            'LineChart rendering is unavailable; bind a ChartRendererInterface or enable ext-gd (GdRenderer).'
        );
    }

    public function bar(array $series, int $width, int $height): Bitmap
    {
        throw new NotImplementedException(
            'BarChart rendering is unavailable; bind a ChartRendererInterface or enable ext-gd (GdRenderer).'
        );
    }
}
