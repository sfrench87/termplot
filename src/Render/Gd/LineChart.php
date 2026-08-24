<?php

declare(strict_types=1);

namespace Termplot\Render\Gd;

use Termplot\Exception\RendererUnavailableException;
use Termplot\Render\Bitmap;

/**
 * Optional GD line chart (D4, A8).
 *
 * Sketch: {@code (new LineChart())->series('qps', $qps)->size(800, 240)->toPng()}
 */
final class LineChart
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
                'LineChart requires ext-gd; install php-gd or use BrailleSparkline / TableFallback.'
            );
        }

        $painter = ChartPainter::create($this->width, $this->height);
        [$min, $max, $sampleCount] = self::bounds($this->seriesList);
        $names = [];
        foreach ($this->seriesList as $s) {
            $names[] = $s['name'];
        }
        $painter->drawChrome($this->title !== '' ? $this->title : 'line', $names, $min, $max, max(1, $sampleCount));

        foreach ($this->seriesList as $i => $s) {
            $values = self::finite($s['values']);
            $n = count($values);
            if ($n === 0) {
                continue;
            }
            $color = $painter->seriesColor($i);
            $prevX = null;
            $prevY = null;
            for ($k = 0; $k < $n; $k++) {
                $x = $painter->mapX($k, $n);
                $y = $painter->mapY($values[$k], $min, $max);
                if ($prevX !== null && $prevY !== null) {
                    $painter->line($prevX, $prevY, $x, $y, $color);
                }
                $painter->dot($x, $y, $color);
                $prevX = $x;
                $prevY = $y;
            }
        }

        return $painter->toPng();
    }

    public function toBitmap(): Bitmap
    {
        return Bitmap::png($this->toPng());
    }

    /**
     * @param list<array{name: string, values: list<int|float>}> $seriesList
     * @return array{0: float, 1: float, 2: int}
     */
    public static function bounds(array $seriesList): array
    {
        $all = [];
        $count = 0;
        foreach ($seriesList as $s) {
            $vals = self::finite($s['values']);
            $count = max($count, count($vals));
            foreach ($vals as $v) {
                $all[] = $v;
            }
        }
        if ($all === []) {
            return [0.0, 1.0, 0];
        }
        $min = min($all);
        $max = max($all);
        if ($max <= $min) {
            $max = $min + 1.0;
        }

        return [$min, $max, $count];
    }

    /**
     * @param list<int|float> $values
     * @return list<float>
     */
    public static function finite(array $values): array
    {
        $out = [];
        foreach ($values as $v) {
            if (is_int($v) || (is_float($v) && is_finite($v))) {
                $out[] = (float) $v;
            }
        }

        return $out;
    }
}
