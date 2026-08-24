<?php

declare(strict_types=1);

namespace Termplot\Fallback;

use Termplot\Exception\NotImplementedException;

/**
 * @todo Willow — fill in table fallback rendering.
 */
final class TableFallback implements TableFallbackInterface
{
    public function render(array $series): string
    {
        throw new NotImplementedException(
            'TableFallback is stubbed for Willow (universal last-resort ladder step).'
        );
    }
}
