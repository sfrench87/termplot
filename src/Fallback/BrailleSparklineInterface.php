<?php

declare(strict_types=1);

namespace Termplot\Fallback;

/**
 * Capability-ladder step after Kitty (and any future graphics backends).
 *
 * Concrete class also offers {@see BrailleSparkline::fromValues()} for the
 * frozen sketch {@code BrailleSparkline::fromValues($values)->render()}.
 */
interface BrailleSparklineInterface
{
    /**
     * @param list<int|float> $series Empty list uses values stored by fromValues().
     */
    public function render(array $series = [], int $width = 40, int $height = 8): string;
}
