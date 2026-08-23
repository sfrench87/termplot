<?php

declare(strict_types=1);

namespace Termplot\Transmit;

use Termplot\Protocol\DeleteAction;
use Termplot\Protocol\PlacementId;
use Termplot\Render\Bitmap;

/**
 * Graphics backend seam (D1). v0.1 ships {@see KittyTransmitter} only.
 */
interface TransmitterInterface
{
    /**
     * Upload pixel data without displaying ({@code a=t}).
     */
    public function transmit(Bitmap $bitmap, PlacementId $placement, ?TransmitOptions $options = null): void;

    /**
     * Place a previously transmitted image ({@code a=p}).
     */
    public function place(PlacementId $placement, ?TransmitOptions $options = null): void;

    /**
     * Transmit and display ({@code a=T}). Same PlacementId replaces in place;
     * MUST NOT delete-first (A4).
     */
    public function replace(Bitmap $bitmap, PlacementId $placement, int $z = -1, bool $cursorMove = false): void;

    public function delete(DeleteAction $action): void;

    /**
     * Graphics protocol query ({@code a=q}).
     */
    public function query(int $imageId = 1): void;
}
