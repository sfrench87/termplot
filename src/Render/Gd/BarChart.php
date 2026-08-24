<?php

declare(strict_types=1);

namespace Termplot\Render\Gd;

use Termplot\Exception\RendererUnavailableException;
use Termplot\Render\Bitmap;

/**
 * Optional GD bar chart (D4, A8). Same fluent shape as {@see LineChart}.
 *
 * Sketch: {@code (new BarChart())->series('qps', $qps)->size(800, 240)->toPng()}
 */
final class BarChart
{
    /** @var list<array{name: string, values: list<int|float>}> */
    private array $seriesList = [];

    private int $width = 800;

    private int $height = 240;

    private string $title = '';

    public static function isAvailable(): bool
    {
        return ChartPainter::isAvailable();
    }

    /**
     * @param list<int|float> $values
     */
    public function series(string $name, array $values): self
    {
        $this->seriesList[] = ['name' => $name, 'values' => array_values($values)];
        if ($this->title === '') {
            $this->title = $name;
        }

        return $this;
    }

    public function size(int $width, int $height): self
    {
        if ($width < 1 || $height < 1) {
            throw new \InvalidArgumentException('size() width and height must be >= 1');
        }
        $this->width = $width;
        $this->height = $height;

        return $this;
    }

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function toPng(): string
    {
        if (!self::isAvailable()) {
            throw new RendererUnavailableException(
                'BarChart requires ext-gd; install php-gd or use BrailleSparkline / TableFallback.'
            );
        }

        $painter = ChartPainter::create($this->width, $this->height);
        [$dataMin, $dataMax, $sampleCount] = LineChart::bounds($this->seriesList);
        $min = min(0.0, $dataMin);
        $max = max(0.0, $dataMax);
        if ($max <= $min) {
            $max = $min + 1.0;
        }
        $names = [];
        foreach ($this->seriesList as $s) {
            $names[] = $s['name'];
        }
        $nSeries = max(1, count($this->seriesList));
        $nCats = max(1, $sampleCount);
        $painter->drawChrome($this->title !== '' ? $this->title : 'bar', $names, $min, $max, $nCats);

        $groupW = $painter->plotWidth() / $nCats;
        $gap = max(1.0, $groupW * 0.12);
        $inner = max(1.0, $groupW - 2 * $gap);
        $barW = max(1, (int) floor($inner / $nSeries) - 1);
        $zeroY = $painter->mapY(0.0, $min, $max);

        foreach ($this->seriesList as $sIdx => $s) {
            $values = LineChart::finite($s['values']);
            $color = $painter->seriesColor($sIdx);
            foreach ($values as $c => $v) {
                $groupLeft = $painter->plotLeft() + (int) round($c * $groupW + $gap);
                $x1 = $groupLeft + $sIdx * ($barW + 1);
                $x2 = $x1 + $barW - 1;
                $y = $painter->mapY($v, $min, $max);
                $top = min($y, $zeroY);
                $bottom = max($y, $zeroY);
                if ($bottom <= $top) {
                    $bottom = $top + 1;
                }
                $painter->fillRect($x1, $top, $x2, $bottom, $color);
            }
        }

        return $painter->toPng();
    }

    public function toBitmap(): Bitmap
    {
        return Bitmap::png($this->toPng());
    }
}
