<?php

declare(strict_types=1);

namespace Termplot\Protocol;

final class NullWriter implements EscapeWriterInterface
{
    public function write(string $bytes): void
    {
    }

    public function flush(): void
    {
    }
}
