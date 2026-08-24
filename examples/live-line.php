#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Fake-metrics live line (W6).
 *
 * Kitty + ext-gd: GD PNG through Dashboard (text pane titles, axes in the bitmap).
 * Otherwise Termplot::line()->draw() walks Braille then table.
 *
 *   php examples/live-line.php
 *
 * TERMPLOT_FRAMES (default 40) and TERMPLOT_SLEEP_US bound the loop for CI.
 */

use Termplot\Dashboard\Dashboard;
use Termplot\Probe\TerminalProbe;
use Termplot\Render\Bitmap;
use Termplot\Render\Gd\LineChart;
use Termplot\Termplot;

$root = dirname(__DIR__);
$autoload = $root . '/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Run composer install first.\n");
    exit(1);
}
require $autoload;

$frames = max(1, (int) (getenv('TERMPLOT_FRAMES') ?: 40));
$sleepUs = max(0, (int) (getenv('TERMPLOT_SLEEP_US') ?: 150000));

$cap = TerminalProbe::detect(STDIN, STDOUT);
$qps = [];
$dash = null;
if ($cap->kitty && extension_loaded('gd')) {
    $dash = Dashboard::create()
        ->pane('throughput', cellRect: [0, 0, 72, 16]);
    $dash->paintChrome();
}

for ($t = 0; $t < $frames; $t++) {
    $qps[] = 80.0 + 35.0 * sin($t / 4.5) + (float) ((($t * 17) % 13) - 6);
    if (count($qps) > 80) {
        array_shift($qps);
    }

    if ($dash !== null) {
        $png = (new LineChart())->series('qps', $qps)->size(800, 240)->title('qps')->toPng();
        $dash->tick(['throughput' => Bitmap::png($png)]);
    } else {
        fwrite(STDOUT, "\033[H");
        Termplot::line($qps)->width(800)->height(240)->draw();
    }

    if ($sleepUs > 0) {
        usleep($sleepUs);
    }
}
