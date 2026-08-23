<?php

declare(strict_types=1);

namespace Termplot\Protocol;

/**
 * Writes Kitty APC / CSI bytes to a sink.
 */
interface EscapeWriterInterface
{
    public function write(string $bytes): void;

    public function flush(): void;
}
