<?php

declare(strict_types=1);

namespace Termplot\Render;

use Termplot\Exception\NotImplementedException;

/**
 * Stub chart renderer used until Willow implements GD LineChart/BarChart.
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
            'LineChart rendering requires GD and is owned by Willow; not implemented in v0.1 core.'
        );
    }

    public function bar(array $series, int $width, int $height): Bitmap
    {
        throw new NotImplementedException(
            'BarChart rendering requires GD and is owned by Willow; not implemented in v0.1 core.'
        );
    }
}
