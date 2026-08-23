# Visual spec (W1)

Termplot’s charts share one chrome language whether they land as a Kitty PNG,
a Braille sparkline, or a last-resort table.

## Palette

Dark-terminal defaults (RGB). GD uses them as pixels; text fallbacks do not emit
color escapes (pipe-safe, no APC).

| Role | RGB | Hex |
|------|-----|-----|
| Background | 13, 17, 23 | `#0d1117` |
| Plot fill | 22, 27, 34 | `#161b22` |
| Grid | 33, 38, 45 | `#21262d` |
| Axis / labels | 139, 148, 158 | `#8b949e` |
| Title | 230, 237, 243 | `#e6edf3` |
| Series 0 | 88, 166, 255 | `#58a6ff` |
| Series 1 | 63, 185, 80 | `#3fb950` |
| Series 2 | 210, 153, 34 | `#d29922` |
| Series 3 | 248, 81, 73 | `#f85149` |

Further series cycle violet / teal. Source of truth: `Termplot\Render\VisualSpec`.

## Margins (GD bitmaps)

| Edge | Pixels | Content |
|------|--------|---------|
| Left | 52 | Y-axis tick labels |
| Right | 16 | Breathing room |
| Top | 28 | Chart / series title |
| Bottom | 32 | X-axis sample index labels |

Plot rectangle is the remaining inner area. Minimum plot is 2×2 px if the
requested image is smaller than the margins.

## Axis chrome

- **GD (Kitty path):** axes, 4 y-ticks, light horizontal grid, title, series
  legend, and sample-index x labels are **painted into the PNG**.
- **Braille:** max/min labels in a 6-character left column; plot body is Unicode
  Braille (`U+2800`–`U+28FF`). No CSI, no APC (`\033_G`).
- **Table:** ASCII columns `idx`, `value`, `#` bar. No CSI, no APC.

## Dashboard chrome vs chart chrome

Pane titles and box borders are **terminal text** (see `docs/DASHBOARD.md`).
Axis ticks and series color live **in the bitmap**. Images sit under text
(`z = -1`) so titles stay sharp.
