<?php

declare(strict_types=1);

namespace Termplot\Fallback;

/**
 * Capability-ladder step after Kitty (and any future graphics backends).
 *
 * @todo Willow — implement Unicode braille / block sparkline body.
 */
interface BrailleSparklineInterface
{
    /**
     * @param list<int|float> $series
     */
    public function render(array $series, int $width = 40, int $height = 8): string;
}
