<?php

declare(strict_types=1);

namespace Termplot\Tests\Support;

/**
 * Canonical 1×1 PNG used by A2 golden tests (well under 1 KB).
 */
final class TinyPng
{
    public static function bytes(): string
    {
        $bytes = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true,
        );
        if ($bytes === false || strlen($bytes) > 1024) {
            throw new \RuntimeException('Tiny PNG fixture is invalid');
        }

        return $bytes;
    }
}
