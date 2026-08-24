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
        self::writeAll($this->stream, $bytes);
    }

    /**
     * Write every byte. {@see fwrite()} may return a short count; 0/false is fatal
     * so a non-blocking sink cannot spin forever.
     *
     * @param resource $stream
     */
    public static function writeAll(mixed $stream, string $bytes): void
    {
        $remaining = $bytes;
        while ($remaining !== '') {
            $n = fwrite($stream, $remaining);
            if ($n === false || $n === 0) {
                throw new \RuntimeException('Failed to write escape sequence to stream');
            }
            $remaining = substr($remaining, $n);
        }
    }

    public function flush(): void
    {
        fflush($this->stream);
    }
}
