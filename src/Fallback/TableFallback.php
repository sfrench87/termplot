<?php

declare(strict_types=1);

namespace Termplot\Fallback;

use Termplot\Render\VisualSpec;

/**
 * Last-resort table (D1, W3). Pipe-safe: no APC ({@code \\033_G}), no CSI.
 */
final class TableFallback implements TableFallbackInterface
{
    public const MAX_ROWS = 120;

    /**
     * @param list<int|float> $series
     */
    public function render(array $series): string
    {
        $values = array_values($series);
        $n = count($values);
        $shown = min($n, self::MAX_ROWS);
        $barW = VisualSpec::TABLE_BAR_WIDTH;

        $finite = [];
        for ($i = 0; $i < $shown; $i++) {
            $v = $values[$i];
            if (is_int($v) || (is_float($v) && is_finite($v))) {
                $finite[] = (float) $v;
            }
        }
        $min = $finite === [] ? 0.0 : min($finite);
        $max = $finite === [] ? 1.0 : max($finite);
        if ($max <= $min) {
            $max = $min + 1.0;
        }

        $idxW = max(3, strlen((string) max(0, $shown - 1)));
        $valW = 8;
        for ($i = 0; $i < $shown; $i++) {
            $valW = max($valW, strlen(VisualSpec::formatTick(is_int($values[$i]) || is_float($values[$i]) ? $values[$i] : 0)));
        }

        $lines = [];
        $lines[] = str_pad('idx', $idxW, ' ', STR_PAD_LEFT)
            . '  ' . str_pad('value', $valW, ' ', STR_PAD_LEFT)
            . '  bar';
        $lines[] = str_repeat('-', $idxW) . '  ' . str_repeat('-', $valW) . '  ' . str_repeat('-', $barW);

        for ($i = 0; $i < $shown; $i++) {
            $v = $values[$i];
            $num = is_int($v) || (is_float($v) && is_finite($v)) ? (float) $v : 0.0;
            $frac = ($num - $min) / ($max - $min);
            $filled = (int) round(max(0.0, min(1.0, $frac)) * $barW);
            $bar = str_repeat('#', $filled) . str_repeat('.', $barW - $filled);
            $lines[] = str_pad((string) $i, $idxW, ' ', STR_PAD_LEFT)
                . '  ' . str_pad(VisualSpec::formatTick($num), $valW, ' ', STR_PAD_LEFT)
                . '  ' . $bar;
        }

        if ($n > $shown) {
            $lines[] = '... (' . ($n - $shown) . ' more)';
        }

        return implode("\n", $lines) . "\n";
    }
}
