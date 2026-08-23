<?php

declare(strict_types=1);

namespace Termplot\Probe;

/**
 * Test double for {@see ProbeIoInterface}. Never blocks.
 */
final class FakeProbeIo implements ProbeIoInterface
{
    /** @var list<string> */
    public array $writes = [];

    /**
     * @param array{cols: ?int, rows: ?int, pixelWidth: ?int, pixelHeight: ?int} $winsize
     * @param array<string, string> $env
     */
    public function __construct(
        public bool $outputTty = false,
        public bool $inputTty = false,
        public string $readBuffer = '',
        public array $env = [],
        public array $winsize = [
            'cols' => 80,
            'rows' => 24,
            'pixelWidth' => 800,
            'pixelHeight' => 600,
        ],
        private mixed $inputToken = 'in',
        private mixed $outputToken = 'out',
    ) {
    }

    public function isatty(mixed $stream): bool
    {
        if ($stream === $this->inputToken) {
            return $this->inputTty;
        }
        if ($stream === $this->outputToken) {
            return $this->outputTty;
        }

        return false;
    }

    public function write(mixed $stream, string $bytes): void
    {
        $this->writes[] = $bytes;
    }

    public function readWithDeadline(mixed $stream, float $seconds): string
    {
        return $this->readBuffer;
    }

    public function getenv(string $name): ?string
    {
        $value = $this->env[$name] ?? null;

        return $value === '' ? null : $value;
    }

    public function winsize(mixed $stream): array
    {
        return $this->winsize;
    }
}
