<?php

declare(strict_types=1);

namespace Termplot\Fallback;

use Termplot\Render\VisualSpec;

/**
 * Unicode Braille sparkline (D1, A7). Never emits APC ({@code \\033_G}) or CSI.
 *
 * Sketch: {@code echo BrailleSparkline::fromValues($values)->render();}
 */
final class BrailleSparkline implements BrailleSparklineInterface
{
    /** Dots 1–8 as [row][col] bit masks inside a Braille cell (2×4). */
    private const DOT = [
        [0x01, 0x08],
        [0x02, 0x10],
        [0x04, 0x20],
        [0x40, 0x80],
    ];

    /** @var list<int|float> */
    private array $values;

    private int $cellWidth;

    private int $cellHeight;

    /**
     * @param list<int|float> $values
     */
    public function __construct(array $values = [], int $width = 40, int $height = 8)
    {
        $this->values = array_values($values);
        $this->cellWidth = max(1, $width);
        $this->cellHeight = max(1, $height);
    }

    /**
     * @param list<int|float> $values
     */
    public static function fromValues(array $values, int $width = 40, int $height = 8): self
    {
        return new self($values, $width, $height);
    }

    /**
     * @param list<int|float> $series
     */
    public function render(array $series = [], int $width = 40, int $height = 8): string
    {
        $data = $series === [] ? $this->values : array_values($series);
        if ($series === []) {
            $width = func_num_args() >= 2 ? $width : $this->cellWidth;
            $height = func_num_args() >= 3 ? $height : $this->cellHeight;
        }
        $width = max(1, $width);
        $height = max(1, $height);

        $finite = [];
        foreach ($data as $v) {
            if (is_int($v) || (is_float($v) && is_finite($v))) {
                $finite[] = (float) $v;
            }
        }

        $dotW = $width * 2;
        $dotH = $height * 4;
        $grid = array_fill(0, $dotH, array_fill(0, $dotW, false));

        $min = 0.0;
        $max = 1.0;
        if ($finite !== []) {
            $min = min($finite);
            $max = max($finite);
            if ($max <= $min) {
                $max = $min + 1.0;
            }
            $n = count($finite);
            $xs = [];
            $ys = [];
            for ($x = 0; $x < $dotW; $x++) {
                $t = $dotW === 1 ? 0.0 : $x / ($dotW - 1);
                $idx = $t * ($n - 1);
                $v = self::sample($finite, $idx);
                $y = (int) round(($max - $v) / ($max - $min) * ($dotH - 1));
                $y = max(0, min($dotH - 1, $y));
                $xs[] = $x;
                $ys[] = $y;
            }
            for ($i = 0; $i < $dotW; $i++) {
                $grid[$ys[$i]][$xs[$i]] = true;
                if ($i > 0) {
                    self::bresenham($grid, $xs[$i - 1], $ys[$i - 1], $xs[$i], $ys[$i]);
                }
            }
        }

        $lines = [];
        $labelW = VisualSpec::BRAILLE_LABEL_WIDTH;
        for ($row = 0; $row < $height; $row++) {
            $cells = '';
            for ($col = 0; $col < $width; $col++) {
                $byte = 0;
                for ($dy = 0; $dy < 4; $dy++) {
                    for ($dx = 0; $dx < 2; $dx++) {
                        $x = $col * 2 + $dx;
                        $y = $row * 4 + $dy;
                        if ($grid[$y][$x]) {
                            $byte |= self::DOT[$dy][$dx];
                        }
                    }
                }
                $cells .= self::brailleChar($byte);
            }
            $prefix = str_repeat(' ', $labelW);
            if ($row === 0) {
                $prefix = str_pad(VisualSpec::formatTick($max), $labelW, ' ', STR_PAD_LEFT);
            } elseif ($row === $height - 1) {
                $prefix = str_pad(VisualSpec::formatTick($min), $labelW, ' ', STR_PAD_LEFT);
            }
            $lines[] = $prefix . ' ' . $cells;
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param list<float> $data
     */
    private static function sample(array $data, float $idx): float
    {
        $n = count($data);
        if ($n === 1) {
            return $data[0];
        }
        $lo = (int) floor($idx);
        $hi = min($n - 1, $lo + 1);
        $frac = $idx - $lo;

        return $data[$lo] * (1.0 - $frac) + $data[$hi] * $frac;
    }

    /**
     * @param array<int, array<int, bool>> $grid
     */
    private static function bresenham(array &$grid, int $x0, int $y0, int $x1, int $y1): void
    {
        $dx = abs($x1 - $x0);
        $dy = abs($y1 - $y0);
        $sx = $x0 < $x1 ? 1 : -1;
        $sy = $y0 < $y1 ? 1 : -1;
        $err = $dx - $dy;
        $x = $x0;
        $y = $y0;
        while (true) {
            $grid[$y][$x] = true;
            if ($x === $x1 && $y === $y1) {
                break;
            }
            $e2 = 2 * $err;
            if ($e2 > -$dy) {
                $err -= $dy;
                $x += $sx;
            }
            if ($e2 < $dx) {
                $err += $dx;
                $y += $sy;
            }
        }
    }

    private static function brailleChar(int $bits): string
    {
        $cp = 0x2800 + ($bits & 0xFF);

        return chr(0xE0 | ($cp >> 12))
            . chr(0x80 | (($cp >> 6) & 0x3F))
            . chr(0x80 | ($cp & 0x3F));
    }
}
