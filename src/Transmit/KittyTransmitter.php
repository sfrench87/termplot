<?php

declare(strict_types=1);

namespace Termplot\Transmit;

use Termplot\Protocol\DeleteAction;
use Termplot\Protocol\EscapeWriter;
use Termplot\Protocol\EscapeWriterInterface;
use Termplot\Protocol\KittyEncoder;
use Termplot\Protocol\PlacementId;
use Termplot\Render\Bitmap;

/**
 * Writes Kitty APC frames through an {@see EscapeWriterInterface}.
 *
 * Sketch: {@code new KittyTransmitter(STDOUT)}
 */
final class KittyTransmitter implements TransmitterInterface
{
    private EscapeWriterInterface $writer;

    public function __construct(mixed $output)
    {
        if ($output instanceof EscapeWriterInterface) {
            $this->writer = $output;
        } else {
            $this->writer = new EscapeWriter($output);
        }
    }

    public function transmit(Bitmap $bitmap, PlacementId $placement, ?TransmitOptions $options = null): void
    {
        $options = ($options ?? new TransmitOptions())->withAction(TransmitOptions::ACTION_TRANSMIT);
        $this->writeFrames(KittyEncoder::encode($bitmap, $placement, $options));
    }

    public function place(PlacementId $placement, ?TransmitOptions $options = null): void
    {
        $options = ($options ?? new TransmitOptions())->withAction(TransmitOptions::ACTION_PLACE);
        $this->writeFrames(KittyEncoder::encodePlace($placement, $options));
    }

    public function replace(Bitmap $bitmap, PlacementId $placement, int $z = -1, bool $cursorMove = false): void
    {
        $options = new TransmitOptions(
            action: TransmitOptions::ACTION_TRANSMIT_AND_DISPLAY,
            z: $z,
            cursorMove: $cursorMove,
        );
        $this->writeFrames(KittyEncoder::encode($bitmap, $placement, $options));
    }

    public function delete(DeleteAction $action): void
    {
        $this->writeFrames(KittyEncoder::encodeDelete($action));
    }

    public function query(int $imageId = 1): void
    {
        $this->writeFrames(KittyEncoder::encodeQuery($imageId));
    }

    /**
     * @param list<string> $frames
     */
    private function writeFrames(array $frames): void
    {
        foreach ($frames as $frame) {
            $this->writer->write($frame);
        }
        $this->writer->flush();
    }
}
