<?php

declare(strict_types=1);

namespace Termplot\Fallback;

/**
 * Universal last-resort fallback (D1).
 *
 * @todo Willow — implement ASCII/UTF-8 table body.
 */
interface TableFallbackInterface
{
    /**
     * @param list<int|float> $series
     */
    public function render(array $series): string;
}
