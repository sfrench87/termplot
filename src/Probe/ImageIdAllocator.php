<?php

declare(strict_types=1);

namespace Termplot\Probe;

/**
 * Auto-allocate Kitty image ids, with optional caller override (D6).
 *
 * Ids are in 1..4294967295 (Kitty forbids 0).
 */
final class ImageIdAllocator
{
    public const MIN = 1;
    public const MAX = 4_294_967_295;

    private int $next;

    public function __construct(int $start = self::MIN)
    {
        $this->assertValid($start);
        $this->next = $start;
    }

    /**
     * Next auto id, or {@code $id} when the caller supplies an override.
     * The high-water mark never rewinds, so later {@see next()} calls cannot collide
     * with a higher override.
     */
    public function next(?int $id = null): int
    {
        if ($id !== null) {
            $this->assertValid($id);
            if ($id >= $this->next) {
                $this->next = $id + 1;
            }

            return $id;
        }

        if ($this->next > self::MAX) {
            throw new \OverflowException('Kitty image id space exhausted');
        }

        $allocated = $this->next;
        $this->next++;

        return $allocated;
    }

    public function peek(): int
    {
        return $this->next;
    }

    private function assertValid(int $id): void
    {
        if ($id < self::MIN || $id > self::MAX) {
            throw new \InvalidArgumentException('image id must be in 1..4294967295');
        }
    }
}
