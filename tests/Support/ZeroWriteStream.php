<?php

declare(strict_types=1);

namespace Termplot\Tests\Support;

/**
 * Stream wrapper whose fwrite() always reports 0 bytes written.
 */
final class ZeroWriteStream
{
    /** @var resource|null Stream context assigned by PHP. */
    public $context;

    public static function register(): void
    {
        if (!in_array('termplot-zero', stream_get_wrappers(), true)) {
            stream_wrapper_register('termplot-zero', self::class);
        }
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        return true;
    }

    public function stream_write(string $data): int
    {
        return 0;
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
