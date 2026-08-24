<?php

declare(strict_types=1);

namespace Termplot\Probe;

/**
 * Capability schema (v0.1): only {@see $kitty} drives the graphics path.
 */
final readonly class Capability
{
    public function __construct(
        public bool $kitty,
        public ?int $pixelWidth,
        public ?int $pixelHeight,
        public int|null $cols,
        public int|null $rows,
        public bool $tty,
    ) {
    }
}
