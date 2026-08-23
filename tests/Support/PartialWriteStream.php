<?php

declare(strict_types=1);

namespace Termplot\Tests\Support;

/**
 * Stream wrapper that writes at most {@see $maxBytesPerWrite} bytes per fwrite().
 */
final class PartialWriteStream
{
    public static int $maxBytesPerWrite = 3;

    /** @var array<string, string> */
    public static array $buffers = [];

    /** @var resource|null Stream context assigned by PHP. */
    public $context;

    private string $id = 'default';

    public static function register(): void
    {
        if (!in_array('termplot-partial', stream_get_wrappers(), true)) {
            stream_wrapper_register('termplot-partial', self::class);
        }
    }

    public static function reset(): void
    {
        self::$buffers = [];
        self::$maxBytesPerWrite = 3;
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        $this->id = substr($path, strlen('termplot-partial://'));
        self::$buffers[$this->id] = '';

        return true;
    }

    public function stream_write(string $data): int
    {
        $n = min(strlen($data), max(0, self::$maxBytesPerWrite));
        self::$buffers[$this->id] .= substr($data, 0, $n);

        return $n;
    }

    public function stream_read(int $count): string
    {
        return '';
    }

    public function stream_eof(): bool
    {
        return true;
    }

    public function stream_flush(): bool
    {
        return true;
    }

    public function stream_stat(): array
    {
        return [];
    }
}
