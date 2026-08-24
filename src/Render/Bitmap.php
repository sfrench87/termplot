<?php

declare(strict_types=1);

namespace Termplot\Render;

/**
 * Pixel buffer ready for Kitty transmission.
 *
 * Formats (Kitty {@code f=}): PNG=100, RGB=24, RGBA=32.
 */
final readonly class Bitmap
{
    public const FORMAT_RGB = 24;
    public const FORMAT_RGBA = 32;
    public const FORMAT_PNG = 100;

    private function __construct(
        public string $bytes,
        public int $format,
        public ?int $width = null,
        public ?int $height = null,
    ) {
        if (!in_array($this->format, [self::FORMAT_RGB, self::FORMAT_RGBA, self::FORMAT_PNG], true)) {
            throw new \InvalidArgumentException('Unsupported Kitty pixel format f=' . $this->format);
        }
    }

    public static function png(string $pngBytes): self
    {
        if ($pngBytes === '') {
            throw new \InvalidArgumentException('PNG payload must not be empty');
        }

        return new self($pngBytes, self::FORMAT_PNG);
    }

    public static function rgb(string $bytes, int $width, int $height): self
    {
        self::assertDimensions($width, $height);
        $expected = $width * $height * 3;
        if (strlen($bytes) !== $expected) {
            throw new \InvalidArgumentException("RGB payload must be {$expected} bytes, got " . strlen($bytes));
        }

        return new self($bytes, self::FORMAT_RGB, $width, $height);
    }

    public static function rgba(string $bytes, int $width, int $height): self
    {
        self::assertDimensions($width, $height);
        $expected = $width * $height * 4;
        if (strlen($bytes) !== $expected) {
            throw new \InvalidArgumentException("RGBA payload must be {$expected} bytes, got " . strlen($bytes));
        }

        return new self($bytes, self::FORMAT_RGBA, $width, $height);
    }

    private static function assertDimensions(int $width, int $height): void
    {
        if ($width < 1 || $height < 1) {
            throw new \InvalidArgumentException('Bitmap width and height must be >= 1');
        }
    }
}
