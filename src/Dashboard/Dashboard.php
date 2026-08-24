<?php

declare(strict_types=1);

namespace Termplot\Dashboard;

use Termplot\Probe\ImageIdAllocator;
use Termplot\Protocol\EscapeWriterInterface;
use Termplot\Protocol\PlacementId;
use Termplot\Render\Bitmap;
use Termplot\Transmit\KittyTransmitter;
use Termplot\Transmit\TransmitterInterface;
use Termplot\Transmit\TransmitOptions;

/**
 * Multi-pane dashboard structure (A9, W5).
 *
 * {@see tick()} emits replace APC only on the transmitter (Merlin). Pane titles
 * and box borders are terminal text via {@see paintChrome()} on an optional
 * chrome stream. When chrome is attached, tick() CUPs to the pane interior on
 * that stream so the bitmap lands inside the box — never on the transmitter.
 *
 * Sketch:
 * {@code Dashboard::create()->pane('throughput', cellRect: [0, 0, 60, 12])->tick([...])}
 */
final class Dashboard
{
    /** @var array<string, Pane> */
    private array $panes = [];

    public function __construct(
        private TransmitterInterface $transmitter,
        private ImageIdAllocator $ids = new ImageIdAllocator(),
        private mixed $chrome = null,
    ) {
    }

    public static function create(
        ?TransmitterInterface $transmitter = null,
        ?ImageIdAllocator $ids = null,
        mixed $output = null,
        mixed $chrome = null,
    ): self {
        if ($transmitter === null) {
            $output ??= defined('STDOUT') ? STDOUT : fopen('php://output', 'w');
            $transmitter = new KittyTransmitter($output);
            $chrome ??= $output;
        }

        return new self($transmitter, $ids ?? new ImageIdAllocator(), $chrome);
    }

    public function withChrome(mixed $chrome): self
    {
        $this->chrome = $chrome;

        return $this;
    }

    /**
     * @param array{0: int, 1: int, 2: int, 3: int} $cellRect col, row, cols, rows (0-based origin)
     */
    public function pane(string $name, array $cellRect): self
    {
        if (isset($this->panes[$name])) {
            throw new \InvalidArgumentException("Duplicate pane '{$name}'");
        }
        if (count($cellRect) !== 4) {
            throw new \InvalidArgumentException('cellRect must be [col, row, cols, rows]');
        }

        [$col, $row, $cols, $rows] = array_values($cellRect);
        $placement = new PlacementId($this->ids->next(), placementId: 1);
        $this->panes[$name] = new Pane(
            $name,
            (int) $col,
            (int) $row,
            (int) $cols,
            (int) $rows,
            $placement,
            z: TransmitOptions::DEFAULT_Z,
        );

        return $this;
    }

    /**
     * Draw pane titles and box borders with CUP. No-op without a chrome stream.
     * Does not write APC.
     */
    public function paintChrome(): void
    {
        if ($this->chrome === null) {
            return;
        }
        foreach ($this->panes as $pane) {
            $this->writeBox($pane);
        }
        $this->flushChrome();
    }

    /**
     * Emit one {@see TransmitterInterface::replace()} per named bitmap.
     * Transmitter frames stay APC-only (A9). Optional CUP goes to the chrome stream.
     *
     * @param array<string, Bitmap> $frames
     */
    public function tick(array $frames): void
    {
        foreach ($frames as $name => $bitmap) {
            if (!is_string($name) || !isset($this->panes[$name])) {
                throw new \InvalidArgumentException("Unknown pane '{$name}'");
            }
            if (!$bitmap instanceof Bitmap) {
                throw new \InvalidArgumentException('tick() values must be Bitmap instances');
            }
            $pane = $this->panes[$name];
            if ($this->chrome !== null) {
                $col = $pane->cols >= 3 ? $pane->col + 1 : $pane->col;
                $row = $pane->rows >= 3 ? $pane->row + 1 : $pane->row;
                $this->cup($row, $col);
            }
            $this->transmitter->replace(
                $bitmap,
                $pane->placement,
                z: $pane->z,
                cursorMove: false,
            );
        }
        $this->flushChrome();
    }

    /**
     * @return array<string, Pane>
     */
    public function panes(): array
    {
        return $this->panes;
    }

    private function writeBox(Pane $pane): void
    {
        $w = $pane->cols;
        $h = $pane->rows;
        $this->cup($pane->row, $pane->col);
        $this->writeChrome($this->topBorder($pane->name, $w));
        $innerH = $h - 2;
        for ($i = 0; $i < $innerH; $i++) {
            $this->cup($pane->row + 1 + $i, $pane->col);
            $this->writeChrome($w === 1 ? '│' : ('│' . str_repeat(' ', $w - 2) . '│'));
        }
        if ($h >= 2) {
            $this->cup($pane->row + $h - 1, $pane->col);
            $this->writeChrome($w === 1 ? '└' : ('└' . str_repeat('─', $w - 2) . '┘'));
        }
    }

    private function topBorder(string $title, int $width): string
    {
        if ($width === 1) {
            return '┌';
        }
        $inner = $width - 2;
        if ($title === '') {
            return '┌' . str_repeat('─', $inner) . '┐';
        }
        $label = ' ' . $title . ' ';
        if (strlen($label) > $inner) {
            $label = strlen($title) > $inner
                ? substr($title, 0, $inner)
                : $title;
        }
        $fill = max(0, $inner - strlen($label));

        return '┌' . $label . str_repeat('─', $fill) . '┐';
    }

    private function cup(int $row0, int $col0): void
    {
        $this->writeChrome(sprintf("\033[%d;%dH", $row0 + 1, $col0 + 1));
    }

    private function writeChrome(string $bytes): void
    {
        if ($this->chrome === null || $bytes === '') {
            return;
        }
        if ($this->chrome instanceof EscapeWriterInterface) {
            $this->chrome->write($bytes);

            return;
        }
        if (is_resource($this->chrome)) {
            fwrite($this->chrome, $bytes);
        }
    }

    private function flushChrome(): void
    {
        if ($this->chrome instanceof EscapeWriterInterface) {
            $this->chrome->flush();
        }
    }
}
