<?php

declare(strict_types=1);

namespace Termplot\Protocol;

/**
 * Stream-backed escape writer (typically STDOUT).
 *
 * {@see StreamWriter} is an alias for this type.
 */
class EscapeWriter implements EscapeWriterInterface
{
    /** @param resource $stream */
    public function __construct(private mixed $stream)
    {
        if (!is_resource($this->stream)) {
            throw new \InvalidArgumentException('EscapeWriter requires a stream resource');
        }
    }

    public function write(string $bytes): void
    {
        $written = fwrite($this->stream, $bytes);
        if ($written === false) {
            throw new \RuntimeException('Failed to write escape sequence to stream');
        }
    }

    public function flush(): void
    {
        fflush($this->stream);
    }
}
