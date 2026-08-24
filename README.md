# Termplot

PHP library for **Kitty graphics** — CLI charts and terminal dashboards.

Composer: **`tbd/termplot`** (interim). Packagist vendor is TBD — **do not publish**.

## Terminal support (D7)

| Terminal | Graphics | Notes |
|----------|----------|--------|
| **Kitty** | Primary | Protocol query (`a=q`) is preferred. `KITTY_WINDOW_ID` / `TERM_PROGRAM=kitty` are secondary heuristics. |
| **Ghostty** | Primary | Same Kitty graphics protocol. `TERM_PROGRAM=ghostty` is a secondary heuristic. |
| **WezTerm** | Caveat | Does **not** set `kitty=true` from env alone. Protocol query may succeed on some builds; treat as experimental. |
| **iTerm2** | Caveat | Not on the v0.1 graphics path. Use Braille or table fallback. |
| **tmux** | Warning | No passthrough automation. Graphics queries often fail inside tmux — use a raw Kitty/Ghostty window. Documented only. |
| Everything else | Fallback | Unicode Braille sparkline, then an ASCII/UTF-8 table. |

Payloads are **PNG** (Kitty `f=100`) or raw RGB/RGBA. There is **no JPEG** path.

## Requirements

- PHP **8.2+**
- **No required runtime extensions or Composer packages.** Dev: `phpunit/phpunit`.
- Optional: `ext-gd` (LineChart / BarChart PNG), `ext-zlib` (Kitty `o=z`), `ext-posix` (TTY helper).
- Forbidden in core: `sugarcraft/candy-mosaic`, `react/promise`, Symfony.

## Install (path / VCS, not Packagist)

```bash
composer require tbd/termplot:*@dev
```

## Quick start

```php
use Termplot\Dashboard\Dashboard;
use Termplot\Fallback\BrailleSparkline;
use Termplot\Probe\ImageIdAllocator;
use Termplot\Probe\TerminalProbe;
use Termplot\Protocol\PlacementId;
use Termplot\Render\Bitmap;
use Termplot\Render\Gd\LineChart;
use Termplot\Termplot;
use Termplot\Transmit\KittyTransmitter;

$cap = TerminalProbe::detect(STDIN, STDOUT);
$tx  = new KittyTransmitter(STDOUT);
$ids = new ImageIdAllocator();

if ($cap->kitty) {
    $tx->replace(Bitmap::png($pngBytes), new PlacementId($ids->next(), placementId: 1), z: -1, cursorMove: false);
} else {
    echo BrailleSparkline::fromValues($values)->render();
}

if (extension_loaded('gd')) {
    $png = (new LineChart())->series('qps', $qps)->size(800, 240)->toPng();
}

Termplot::line($series)->width(800)->height(240)->draw();

$dash = Dashboard::create()
    ->pane('throughput', cellRect: [0, 0, 60, 12])
    ->pane('latency', cellRect: [0, 13, 60, 10]);
$dash->paintChrome();
$dash->tick(['throughput' => $bmpA, 'latency' => $bmpB]);
```

`Termplot::line()->width()->height()->draw()` probes the terminal and walks the
ladder: Kitty + GD PNG → Braille → table.

## Capability ladder (D1)

Kitty (v0.1) → future graphics backends → Braille → Table (universal last resort).

Default image z is under text (`z=-1`). Axis ticks live in the PNG; pane titles
are terminal text — see [docs/DASHBOARD.md](docs/DASHBOARD.md) and
[docs/VISUAL_SPEC.md](docs/VISUAL_SPEC.md).

## Live example

```bash
php examples/live-line.php
```

Fake QPS sine wave. In Kitty/Ghostty with `ext-gd` it paints a PNG dashboard;
otherwise it prints a Braille sparkline. `TERMPLOT_FRAMES` / `TERMPLOT_SLEEP_US`
bound the loop.

## Development

```bash
composer install
vendor/bin/phpunit          # unit tests; tty group skipped
KITTY_TEST=1 vendor/bin/phpunit --group tty   # optional live Kitty/Ghostty
```

CI runs PHPUnit **without** `KITTY_TEST` and without a real TTY. GD tests skip
when `ext-gd` is absent.

## Module map

| Path | Owner |
|------|--------|
| `src/Protocol`, `src/Probe`, `src/Transmit` | Merlin (protocol core) |
| `src/Dashboard` | Merlin structure + ids; Willow chrome/CUP |
| `src/Termplot.php` | Thin facade (ladder) |
| `src/Render/Gd` | Willow LineChart / BarChart (`ext-gd`) |
| `src/Fallback` | Willow Braille + table |
| `docs/VISUAL_SPEC.md` | Shared colors / margins / axes |

License: **MIT**. Packagist vendor remains TBD — do not publish.
