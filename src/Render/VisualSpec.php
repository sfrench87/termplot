<?php

declare(strict_types=1);

namespace Termplot\Render;

/**
 * Shared visual language (W1) for GD charts, Braille sparklines, and table fallback.
 *
 * Palette is GitHub-dark adjacent so plots read on typical dark terminals.
 * GD charts paint these as pixels; text fallbacks use the same margins/axis roles.
 *
 * @see docs/VISUAL_SPEC.md
 */
final class VisualSpec
{
    public const BACKGROUND = [13, 17, 23];

    public const PLOT_FILL = [22, 27, 34];

    public const GRID = [33, 38, 45];

    public const AXIS = [139, 148, 158];

    public const LABEL = [139, 148, 158];

    public const TITLE = [230, 237, 243];

    /** @var list<array{0: int, 1: int, 2: int}> */
    public const SERIES = [
        [88, 166, 255],
        [63, 185, 80],
        [210, 153, 34],
        [248, 81, 73],
        [163, 113, 247],
        [57, 217, 201],
    ];

    public const MARGIN_LEFT = 52;

    public const MARGIN_RIGHT = 16;

    public const MARGIN_TOP = 28;

    public const MARGIN_BOTTOM = 32;

    public const AXIS_TICKS = 4;

    public const LINE_THICKNESS = 2;

    public const POINT_RADIUS = 3;

    /** Character columns reserved for y-axis labels in Braille output. */
    public const BRAILLE_LABEL_WIDTH = 6;

    /** ASCII bar width in the table fallback. */
    public const TABLE_BAR_WIDTH = 20;

    /**
     * @return array{0: int, 1: int, 2: int, 3: int} left, top, right, bottom (inclusive pixel edges of the plot)
     */
    public static function plotRect(int $width, int $height): array
    {
        $left = min(self::MARGIN_LEFT, max(0, $width - 8));
        $top = min(self::MARGIN_TOP, max(0, $height - 8));
        $right = max($left + 1, $width - self::MARGIN_RIGHT);
        $bottom = max($top + 1, $height - self::MARGIN_BOTTOM);

        return [$left, $top, $right, $bottom];
    }

    /**
     * @param array{0: int, 1: int, 2: int} $rgb
     */
    public static function seriesColor(int $index): array
    {
        $n = count(self::SERIES);

        return self::SERIES[(($index % $n) + $n) % $n];
    }

    public static function formatTick(int|float $value): string
    {
        if (!is_finite((float) $value)) {
            return 'nan';
        }
        $abs = abs((float) $value);
        if ($abs >= 100) {
            return sprintf('%.0f', $value);
        }
        if ($abs >= 10) {
            return sprintf('%.1f', $value);
        }
        if (is_int($value) || abs($value - round((float) $value)) < 1e-9) {
            return (string) (int) round((float) $value);
        }

        return sprintf('%.2f', $value);
    }
}
