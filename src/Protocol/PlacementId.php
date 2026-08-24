<?php

declare(strict_types=1);

namespace Termplot\Protocol;

/**
 * Kitty image + placement identity.
 *
 * Sketch: {@code new PlacementId($ids->next(), placementId: 1)}
 */
final readonly class PlacementId
{
    public const MIN_IMAGE_ID = 1;
    public const MAX_ID = 4_294_967_295;

    public function __construct(
        public int $imageId,
        public int $placementId = 0,
    ) {
        if ($this->imageId < self::MIN_IMAGE_ID || $this->imageId > self::MAX_ID) {
            throw new \InvalidArgumentException('imageId must be in 1..4294967295');
        }
        if ($this->placementId < 0 || $this->placementId > self::MAX_ID) {
            throw new \InvalidArgumentException('placementId must be in 0..4294967295');
        }
    }

    public function hasPlacementId(): bool
    {
        return $this->placementId > 0;
    }
}
