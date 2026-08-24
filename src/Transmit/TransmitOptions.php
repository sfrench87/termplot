<?php

declare(strict_types=1);

namespace Termplot\Transmit;

/**
 * Options for a Kitty transmit / place / replace command.
 *
 * v0.1 keys: a=T|t|p|d|q; f from the Bitmap; t=d only; m via chunker;
 * o=z optional; i=, p=, c=, r=, C=1, q=2, N=1; z negative by default.
 */
final readonly class TransmitOptions
{
    public const ACTION_TRANSMIT_AND_DISPLAY = 'T';
    public const ACTION_TRANSMIT = 't';
    public const ACTION_PLACE = 'p';
    public const ACTION_DELETE = 'd';
    public const ACTION_QUERY = 'q';

    public const DEFAULT_Z = -1;
    public const QUIET_NONE = 0;
    public const QUIET_OK = 1;
    public const QUIET_ALL = 2;

    public function __construct(
        public string $action = self::ACTION_TRANSMIT_AND_DISPLAY,
        public int $z = self::DEFAULT_Z,
        public bool $cursorMove = false,
        public ?int $columns = null,
        public ?int $rows = null,
        public int $quiet = self::QUIET_ALL,
        public bool $transient = false,
        public bool $compress = false,
    ) {
        if (!in_array($this->action, [
            self::ACTION_TRANSMIT_AND_DISPLAY,
            self::ACTION_TRANSMIT,
            self::ACTION_PLACE,
            self::ACTION_DELETE,
            self::ACTION_QUERY,
        ], true)) {
            throw new \InvalidArgumentException('Unsupported Kitty action a=' . $this->action);
        }
        if ($this->quiet < 0 || $this->quiet > 2) {
            throw new \InvalidArgumentException('quiet (q=) must be 0, 1, or 2');
        }
        if ($this->columns !== null && $this->columns < 1) {
            throw new \InvalidArgumentException('columns (c=) must be >= 1');
        }
        if ($this->rows !== null && $this->rows < 1) {
            throw new \InvalidArgumentException('rows (r=) must be >= 1');
        }
    }

    public function withAction(string $action): self
    {
        return new self(
            action: $action,
            z: $this->z,
            cursorMove: $this->cursorMove,
            columns: $this->columns,
            rows: $this->rows,
            quiet: $this->quiet,
            transient: $this->transient,
            compress: $this->compress,
        );
    }
}
