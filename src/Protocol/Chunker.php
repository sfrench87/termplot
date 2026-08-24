<?php

declare(strict_types=1);

namespace Termplot\Protocol;

/**
 * Split a base64 Kitty payload on the 4096-byte boundary.
 *
 * Kitty requires non-final chunks to be a multiple of 4 bytes; 4096 satisfies that.
 */
final class Chunker
{
    public const SIZE = 4096;

    /**
     * @return list<string>
     */
    public static function split(string $base64Payload, int $size = self::SIZE): array
    {
        if ($size < 4 || $size % 4 !== 0) {
            throw new \InvalidArgumentException('Chunk size must be a positive multiple of 4');
        }

        if ($base64Payload === '') {
            return [''];
        }

        /** @var list<string> $chunks */
        $chunks = str_split($base64Payload, $size);

        return $chunks;
    }
}
