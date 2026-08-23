# termplot

PHP library for Kitty graphics — CLI charting and terminal dashboards (Composer: TBD/termplot).

> **Packagist vendor is TBD.** The Composer name `tbd/termplot` is an interim placeholder
> and **MUST NOT be published** to Packagist until Gendo/Eden freeze a vendor.

Primary graphics path: **Kitty** (Ghostty-compatible). Braille + table fallbacks and GD
Line/Bar charts are stubbed for Willow. Final README marketing copy is Willow’s.

## Requirements

- PHP **8.2+**
- **No required runtime extensions or packages.** Dev: `phpunit/phpunit`.
- `ext-gd` is optional and owned by Willow (not implemented in v0.1 core).
- Forbidden in core: `sugarcraft/candy-mosaic`, `react/promise`, Symfony.

## Install (path / VCS, not Packagist)

```bash
composer require tbd/termplot:*@dev
```

## Quick start (frozen v0.1 sketch)

```php
use Termplot\Dashboard\Dashboard;
use Termplot\Probe\ImageIdAllocator;
use Termplot\Probe\TerminalProbe;
use Termplot\Protocol\PlacementId;
use Termplot\Render\Bitmap;
use Termplot\Termplot;
use Termplot\Transmit\KittyTransmitter;

$cap = TerminalProbe::detect(STDIN, STDOUT);
$tx  = new KittyTransmitter(STDOUT);
$ids = new ImageIdAllocator();
if ($cap->kitty) {
    $tx->replace(Bitmap::png($pngBytes), new PlacementId($ids->next(), placementId: 1), z: -1, cursorMove: false);
} else {
    // Fallback interface — Willow implements BrailleSparkline / TableFallback
}

Termplot::line($series)->width(800)->height(240)->draw(); // probes + ladder; charts stubbed without GD/Willow

$dash = Dashboard::create()
    ->pane('throughput', cellRect: [0, 0, 60, 12])
    ->pane('latency', cellRect: [0, 13, 60, 10]);
$dash->tick(['throughput' => $bmpA, 'latency' => $bmpB]);
```

## Capability ladder (D1)

Kitty (v0.1) → future graphics backends → Braille → Table (universal last resort).
`TransmitterInterface` is the graphics backend seam. Only `Capability::$kitty` drives
the graphics path in v0.1. Default image z is under text (`z=-1`).

## Development

```bash
composer install
vendor/bin/phpunit          # unit tests; tty group skipped
KITTY_TEST=1 vendor/bin/phpunit --group tty   # optional live Kitty/Ghostty
```

CI runs PHPUnit **without** `KITTY_TEST` and without a real TTY.

## Module map

| Path | Owner |
|------|--------|
| `src/Protocol`, `src/Probe`, `src/Transmit`, `src/Dashboard`, `src/Termplot.php` | Merlin (this drop) |
| `src/Render/ChartRendererInterface`, `NullRenderer` | seam; GD bodies Willow |
| `src/Fallback/*` | interfaces + stubs; bodies Willow |
