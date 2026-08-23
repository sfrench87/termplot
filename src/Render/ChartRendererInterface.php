<?php

declare(strict_types=1);

namespace Termplot\Render;

/**
 * Chart backend for the Kitty path. {@see \Termplot\Render\Gd\GdRenderer} is the default.
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
