<?php

declare(strict_types=1);

namespace Termplot\Render;

/**
 * GD Line/Bar rendering is Willow's concern. Core ships a null implementation.
 */
interface ChartRendererInterface
{
    public function isAvailable(): bool;

    /**
     * @param list<int|float> $series
     */
    public function line(array $series, int $width, int $height): Bitmap;

    /**
     * @param list<int|float> $series
     */
    public function bar(array $series, int $width, int $height): Bitmap;
}
