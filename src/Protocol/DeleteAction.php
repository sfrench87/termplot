<?php

declare(strict_types=1);

namespace Termplot\Protocol;

/**
 * Kitty delete action {@code a=d} plus the {@code d} selector.
 *
 * Lowercase {@code d} values hide placements; uppercase also frees pixel data
 * when nothing else references the image.
 */
final readonly class DeleteAction
{
    private function __construct(
        public string $selector,
        public ?int $imageId = null,
        public ?int $placementId = null,
        public ?int $x = null,
        public ?int $y = null,
        public ?int $z = null,
    ) {
        if ($this->selector === '' || strlen($this->selector) !== 1) {
            throw new \InvalidArgumentException('Delete selector d= must be a single character');
        }
    }

    /**
     * Delete all placements visible on screen ({@code d=a} / {@code d=A}).
     */
    public static function all(bool $freeData = false): self
    {
        return new self($freeData ? 'A' : 'a');
    }

    /**
     * Delete by image id, optionally a single placement ({@code d=i} / {@code d=I}).
     */
    public static function byImage(int $imageId, ?int $placementId = null, bool $freeData = false): self
    {
        if ($imageId < PlacementId::MIN_IMAGE_ID || $imageId > PlacementId::MAX_ID) {
            throw new \InvalidArgumentException('imageId must be in 1..4294967295');
        }

        return new self($freeData ? 'I' : 'i', $imageId, $placementId);
    }

    /**
     * Delete placements intersecting the current cursor ({@code d=c} / {@code d=C}).
     */
    public static function atCursor(bool $freeData = false): self
    {
        return new self($freeData ? 'C' : 'c');
    }

    /**
     * Delete placements with the given z-index ({@code d=z} / {@code d=Z}).
     */
    public static function byZ(int $z, bool $freeData = false): self
    {
        return new self($freeData ? 'Z' : 'z', z: $z);
    }

    /**
     * Control keys for the APC frame (excluding {@code a=d}, which the encoder adds).
     *
     * @return array<string, string>
     */
    public function controlKeys(): array
    {
        $keys = ['d' => $this->selector];
        if ($this->imageId !== null) {
            $keys['i'] = (string) $this->imageId;
        }
        if ($this->placementId !== null && $this->placementId > 0) {
            $keys['p'] = (string) $this->placementId;
        }
        if ($this->x !== null) {
            $keys['x'] = (string) $this->x;
        }
        if ($this->y !== null) {
            $keys['y'] = (string) $this->y;
        }
        if ($this->z !== null) {
            $keys['z'] = (string) $this->z;
        }

        return $keys;
    }
}
