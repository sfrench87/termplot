<?php

declare(strict_types=1);

namespace Termplot\Fallback;

use Termplot\Exception\NotImplementedException;

/**
 * @todo Willow — fill in braille sparkline rendering.
 */
final class BrailleSparkline implements BrailleSparklineInterface
{
    public function render(array $series, int $width = 40, int $height = 8): string
    {
        throw new NotImplementedException(
            'BrailleSparkline is stubbed for Willow (ladder step after Kitty).'
        );
    }
}
