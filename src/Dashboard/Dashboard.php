<?php

declare(strict_types=1);

namespace Termplot\Dashboard;

use Termplot\Probe\ImageIdAllocator;
use Termplot\Protocol\PlacementId;
use Termplot\Render\Bitmap;
use Termplot\Transmit\KittyTransmitter;
use Termplot\Transmit\TransmitterInterface;
use Termplot\Transmit\TransmitOptions;

/**
 * Multi-pane dashboard structure (A9).
 *
 * Pane chrome is Willow's. Merlin guarantees: auto-distinct image ids,
 * {@see tick()} emits one replace per named bitmap, default z &lt; 0.
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
    ) {
    }

    public static function create(
        ?TransmitterInterface $transmitter = null,
        ?ImageIdAllocator $ids = null,
        mixed $output = null,
    ): self {
        $transmitter ??= new KittyTransmitter(
            $output ?? (defined('STDOUT') ? STDOUT : fopen('php://output', 'w')),
        );

        return new self($transmitter, $ids ?? new ImageIdAllocator());
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
     * Emit one {@see TransmitterInterface::replace()} per named bitmap.
     * Does not emit CSI CUP; pane chrome and cursor placement are Willow's.
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
            $this->transmitter->replace(
                $bitmap,
                $pane->placement,
                z: $pane->z,
                cursorMove: false,
            );
        }
    }

    /**
     * @return array<string, Pane>
     */
    public function panes(): array
    {
        return $this->panes;
    }
}
