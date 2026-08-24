# Dashboard layout (W5)

## Choice: text pane titles, in-bitmap axes

Termplot dashboards use **terminal text** for pane titles and box borders, and
**in-bitmap** axis chrome (grid, ticks, series color) from GD charts.

Kitty graphics default to `z = -1` (under text). Text titles stay crisp at any
font size; plot axes scale with the PNG.

## What `tick()` does

`Dashboard::tick()` emits **replace APC only** (`a=T`). It does **not** write
CSI CUP onto the transmitter. That is Merlin’s A9 contract — chrome must not
fight the encoder/transmitter.

## What Willow adds

- `Dashboard::paintChrome()` writes CUP (`\033[row;colH`, 1-based) plus Unicode
  box drawing and the pane name onto an optional **chrome stream**.
- When a chrome stream is attached, `tick()` also CUPs to the pane’s inner cell
  on that stream (not on the transmitter) so the next `replace()` lands inside
  the box. Subsequent ticks reuse the same image id; pixels update in place.
- `Dashboard::create()` with the default STDOUT transmitter attaches chrome to
  the same stream. Tests that inject a transmitter leave chrome unset, so A9
  still sees APC-only frames.

## Why not all-in-bitmap titles?

Baking pane names into every PNG would blur at odd cell sizes, fight `z = -1`
overlay text, and force a redraw of titles on every metric tick. Text chrome is
painted once; ticks only replace the plot bitmap.

## tmux

No tmux passthrough automation. If you wrap Kitty/Ghostty in tmux, graphics
queries often fail — use a raw Kitty/Ghostty window for the dashboard (see
README).
