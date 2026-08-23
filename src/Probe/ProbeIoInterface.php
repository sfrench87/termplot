<?php

declare(strict_types=1);

namespace Termplot\Probe;

/**
 * IO seam so protocol queries are mockable and cannot hang unit tests.
 */
interface ProbeIoInterface
{
    public function isatty(mixed $stream): bool;

    public function write(mixed $stream, string $bytes): void;

    /**
     * Block up to {@code $seconds} and return bytes read (possibly empty).
     */
    public function readWithDeadline(mixed $stream, float $seconds): string;

    public function getenv(string $name): ?string;

    /**
     * @return array{cols: ?int, rows: ?int, pixelWidth: ?int, pixelHeight: ?int}
     */
    public function winsize(mixed $stream): array;
}
