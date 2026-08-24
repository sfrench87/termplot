<?php

declare(strict_types=1);

namespace Termplot\Dashboard;

use Termplot\Protocol\PlacementId;

/**
 * Named pane. Chrome (borders, titles) is Willow's; this holds geometry + image id.
 *
 * {@code $cellRect} is stored as 0-based {@see $col}, {@see $row}, {@see $cols}, {@see $rows}.
 */
final readonly class Pane
{
    public function __construct(
        public string $name,
        public int $col,
        public int $row,
        public int $cols,
        public int $rows,
        public PlacementId $placement,
        public int $z = -1,
    ) {
        if ($this->name === '') {
            throw new \InvalidArgumentException('Pane name must not be empty');
        }
        if ($this->cols < 1 || $this->rows < 1) {
            throw new \InvalidArgumentException('Pane cellRect width/height must be >= 1');
        }
        if ($this->col < 0 || $this->row < 0) {
            throw new \InvalidArgumentException('Pane cellRect origin must be >= 0');
        }
    }

    /**
     * @return array{0: int, 1: int, 2: int, 3: int}
     */
    public function cellRect(): array
    {
        return [$this->col, $this->row, $this->cols, $this->rows];
    }
}
