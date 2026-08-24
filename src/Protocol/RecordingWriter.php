<?php

declare(strict_types=1);

namespace Termplot\Protocol;

/**
 * Captures written frames for unit tests (no TTY required).
 */
final class RecordingWriter implements EscapeWriterInterface
{
    /** @var list<string> */
    private array $writes = [];

    public function write(string $bytes): void
    {
        $this->writes[] = $bytes;
    }

    public function flush(): void
    {
    }

    /**
     * @return list<string>
     */
    public function frames(): array
    {
        return $this->writes;
    }

    public function concatenated(): string
    {
        return implode('', $this->writes);
    }

    public function reset(): void
    {
        $this->writes = [];
    }
}
