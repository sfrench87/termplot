<?php

declare(strict_types=1);

namespace Termplot\Render\Gd;

use Termplot\Exception\RendererUnavailableException;
use Termplot\Render\VisualSpec;

/**
 * Shared GD canvas: background, plot, grid, axes, title, legend, PNG encode.
 *
 * @internal
 */
final class ChartPainter
{
    private const FONT_LABEL = 2;

    private const FONT_TITLE = 3;

    /** @var \GdImage */
    private $im;

    private int $width;

    private int $height;

    private int $bg;

    private int $plotBg;

    private int $grid;

    private int $axis;

    private int $label;

    private int $titleColor;

    /** @var list<int> */
    private array $seriesColors = [];

    private int $plotLeft;

    private int $plotTop;

    private int $plotRight;

    private int $plotBottom;

    private function __construct()
    {
    }

    public static function isAvailable(): bool
    {
        return extension_loaded('gd')
            && function_exists('imagecreatetruecolor')
            && function_exists('imagepng');
    }

    public static function create(int $width, int $height): self
    {
        if (!self::isAvailable()) {
            throw new RendererUnavailableException(
                'LineChart/BarChart require ext-gd; install php-gd or use Braille/table fallbacks.'
            );
        }
        if ($width < 1 || $height < 1) {
            throw new \InvalidArgumentException('Chart size must be >= 1x1');
        }

        $im = imagecreatetruecolor($width, $height);
        if ($im === false) {
            throw new RendererUnavailableException('imagecreatetruecolor() failed');
        }

        $self = new self();
        $self->im = $im;
        $self->width = $width;
        $self->height = $height;
        $self->bg = self::color($im, VisualSpec::BACKGROUND);
        $self->plotBg = self::color($im, VisualSpec::PLOT_FILL);
        $self->grid = self::color($im, VisualSpec::GRID);
        $self->axis = self::color($im, VisualSpec::AXIS);
        $self->label = self::color($im, VisualSpec::LABEL);
        $self->titleColor = self::color($im, VisualSpec::TITLE);
        foreach (VisualSpec::SERIES as $rgb) {
            $self->seriesColors[] = self::color($im, $rgb);
        }
        [$self->plotLeft, $self->plotTop, $self->plotRight, $self->plotBottom] = VisualSpec::plotRect($width, $height);

        imagefilledrectangle($im, 0, 0, $width - 1, $height - 1, $self->bg);
        imagefilledrectangle(
            $im,
            $self->plotLeft,
            $self->plotTop,
            $self->plotRight,
            $self->plotBottom,
            $self->plotBg,
        );

        if (function_exists('imageantialias')) {
            @imageantialias($im, true);
        }

        return $self;
    }

    public function plotLeft(): int
    {
        return $this->plotLeft;
    }

    public function plotTop(): int
    {
        return $this->plotTop;
    }

    public function plotRight(): int
    {
        return $this->plotRight;
    }

    public function plotBottom(): int
    {
        return $this->plotBottom;
    }

    public function plotWidth(): int
    {
        return max(1, $this->plotRight - $this->plotLeft);
    }

    public function plotHeight(): int
    {
        return max(1, $this->plotBottom - $this->plotTop);
    }

    public function seriesColor(int $index): int
    {
        $n = count($this->seriesColors);

        return $this->seriesColors[(($index % $n) + $n) % $n];
    }

    /**
     * @param list<string> $names
     */
    public function drawChrome(string $title, array $names, float $min, float $max, int $sampleCount): void
    {
        $this->drawGrid($min, $max);
        $this->drawAxes();
        $this->drawYLabels($min, $max);
        $this->drawXLabels($sampleCount);
        $this->drawTitle($title);
        $this->drawLegend($names);
    }

    public function mapX(int $index, int $count): int
    {
        if ($count <= 1) {
            return intdiv($this->plotLeft + $this->plotRight, 2);
        }
        $t = $index / ($count - 1);

        return (int) round($this->plotLeft + $t * $this->plotWidth());
    }

    public function mapY(float $value, float $min, float $max): int
    {
        if ($max <= $min) {
            $max = $min + 1.0;
        }
        $t = ($value - $min) / ($max - $min);

        return (int) round($this->plotBottom - $t * $this->plotHeight());
    }

    public function line(int $x0, int $y0, int $x1, int $y1, int $color): void
    {
        imagesetthickness($this->im, VisualSpec::LINE_THICKNESS);
        imageline($this->im, $x0, $y0, $x1, $y1, $color);
        imagesetthickness($this->im, 1);
    }

    public function fillRect(int $x1, int $y1, int $x2, int $y2, int $color): void
    {
        imagefilledrectangle($this->im, $x1, $y1, $x2, $y2, $color);
    }

    public function dot(int $x, int $y, int $color): void
    {
        $r = VisualSpec::POINT_RADIUS;
        imagefilledellipse($this->im, $x, $y, $r * 2, $r * 2, $color);
    }

    public function toPng(): string
    {
        ob_start();
        $ok = imagepng($this->im);
        $png = (string) ob_get_clean();
        imagedestroy($this->im);
        if ($ok === false || $png === '' || !str_starts_with($png, "\x89PNG")) {
            throw new RendererUnavailableException('imagepng() failed to encode a PNG');
        }

        return $png;
    }

    /**
     * @param array{0: int, 1: int, 2: int} $rgb
     * @param \GdImage $im
     */
    private static function color($im, array $rgb): int
    {
        $c = imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
        if ($c === false) {
            throw new RendererUnavailableException('imagecolorallocate() failed');
        }

        return $c;
    }

    private function drawGrid(float $min, float $max): void
    {
        $ticks = VisualSpec::AXIS_TICKS;
        for ($i = 0; $i <= $ticks; $i++) {
            $v = $min + ($max - $min) * ($i / $ticks);
            $y = $this->mapY($v, $min, $max);
            imageline($this->im, $this->plotLeft, $y, $this->plotRight, $y, $this->grid);
        }
    }

    private function drawAxes(): void
    {
        imageline($this->im, $this->plotLeft, $this->plotTop, $this->plotLeft, $this->plotBottom, $this->axis);
        imageline($this->im, $this->plotLeft, $this->plotBottom, $this->plotRight, $this->plotBottom, $this->axis);
    }

    private function drawYLabels(float $min, float $max): void
    {
        $ticks = VisualSpec::AXIS_TICKS;
        $fh = imagefontheight(self::FONT_LABEL);
        $fw = imagefontwidth(self::FONT_LABEL);
        for ($i = 0; $i <= $ticks; $i++) {
            $v = $min + ($max - $min) * ($i / $ticks);
            $text = VisualSpec::formatTick($v);
            $y = $this->mapY($v, $min, $max) - intdiv($fh, 2);
            $x = $this->plotLeft - 4 - $fw * strlen($text);
            if ($x < 1) {
                $x = 1;
            }
            imagestring($this->im, self::FONT_LABEL, $x, max(0, $y), $text, $this->label);
        }
    }

    private function drawXLabels(int $sampleCount): void
    {
        if ($sampleCount < 1) {
            return;
        }
        $fw = imagefontwidth(self::FONT_LABEL);
        $marks = min(6, $sampleCount);
        $usedRight = -1000;
        for ($i = 0; $i < $marks; $i++) {
            $idx = $marks === 1 ? 0 : (int) round($i * ($sampleCount - 1) / ($marks - 1));
            $text = (string) $idx;
            $x = $this->mapX($idx, $sampleCount) - intdiv($fw * strlen($text), 2);
            if ($x <= $usedRight + 4) {
                continue;
            }
            $usedRight = $x + $fw * strlen($text);
            imagestring(
                $this->im,
                self::FONT_LABEL,
                max($this->plotLeft, $x),
                min($this->height - $fh = imagefontheight(self::FONT_LABEL), $this->plotBottom + 4),
                $text,
                $this->label,
            );
        }
        unset($fh);
    }

    private function drawTitle(string $title): void
    {
        if ($title === '') {
            return;
        }
        imagestring($this->im, self::FONT_TITLE, $this->plotLeft, 6, $title, $this->titleColor);
    }

    /**
     * @param list<string> $names
     */
    private function drawLegend(array $names): void
    {
        if ($names === []) {
            return;
        }
        $fw = imagefontwidth(self::FONT_LABEL);
        $fh = imagefontheight(self::FONT_LABEL);
        $x = $this->plotRight;
        for ($i = count($names) - 1; $i >= 0; $i--) {
            $name = $names[$i];
            if ($name === '') {
                $name = 's' . $i;
            }
            $box = 8;
            $w = $box + 4 + $fw * strlen($name);
            $x -= $w + 8;
            if ($x < $this->plotLeft) {
                break;
            }
            $color = $this->seriesColor($i);
            imagefilledrectangle($this->im, $x, 8, $x + $box, 8 + $box, $color);
            imagestring($this->im, self::FONT_LABEL, $x + $box + 4, 6, $name, $this->label);
            unset($fh);
        }
    }
}
