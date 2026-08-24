<?php

declare(strict_types=1);

namespace Termplot\Fallback;

/**
 * Universal last-resort fallback (D1). ASCII/UTF-8 table; no APC, no CSI.
 */
interface TableFallbackInterface
{
    /**
     * @param list<int|float> $series
     */
    public function render(array $series): string;
}
