<?php

declare(strict_types=1);

namespace Termplot\Probe;

/**
 * POSIX / CLI implementation of probe IO.
 */
final class PosixProbeIo implements ProbeIoInterface
{
    public function isatty(mixed $stream): bool
    {
        if (!is_resource($stream)) {
            return false;
        }

        if (function_exists('stream_isatty')) {
            return @stream_isatty($stream);
        }

        if (function_exists('posix_isatty')) {
            $fd = @get_resource_id($stream);
            if (!is_int($fd)) {
                return false;
            }

            return @posix_isatty($fd);
        }

        return false;
    }

    public function write(mixed $stream, string $bytes): void
    {
        if (!is_resource($stream)) {
            return;
        }
        fwrite($stream, $bytes);
        fflush($stream);
    }

    public function readWithDeadline(mixed $stream, float $seconds): string
    {
        if (!is_resource($stream) || $seconds <= 0) {
            return '';
        }

        $previous = stream_get_meta_data($stream)['blocked'] ?? true;
        stream_set_blocking($stream, false);

        try {
            $buffer = '';
            $deadline = microtime(true) + $seconds;
            while (microtime(true) < $deadline) {
                $remain = $deadline - microtime(true);
                if ($remain <= 0) {
                    break;
                }
                $read = [$stream];
                $write = null;
                $except = null;
                $sec = (int) $remain;
                $usec = (int) (($remain - $sec) * 1_000_000);
                $n = @stream_select($read, $write, $except, $sec, $usec);
                if ($n === false || $n === 0) {
                    continue;
                }
                $chunk = fread($stream, 4096);
                if ($chunk === false || $chunk === '') {
                    continue;
                }
                $buffer .= $chunk;
                if (self::responseLooksComplete($buffer)) {
                    break;
                }
            }

            return $buffer;
        } finally {
            stream_set_blocking($stream, $previous);
        }
    }

    public function getenv(string $name): ?string
    {
        $value = getenv($name);
        if ($value === false || $value === '') {
            return null;
        }

        return $value;
    }

    public function winsize(mixed $stream): array
    {
        $cols = self::positiveEnv('COLUMNS');
        $rows = self::positiveEnv('LINES');

        if ($this->isatty($stream)) {
            $stty = @exec('stty size 2>/dev/null');
            if (is_string($stty) && preg_match('/^(\d+)\s+(\d+)$/', trim($stty), $m) === 1) {
                $rows = (int) $m[1];
                $cols = (int) $m[2];
            }
        }

        return [
            'cols' => $cols,
            'rows' => $rows,
            'pixelWidth' => null,
            'pixelHeight' => null,
        ];
    }

    private static function positiveEnv(string $name): ?int
    {
        $value = getenv($name);
        if ($value === false || $value === '' || !is_numeric($value)) {
            return null;
        }
        $n = (int) $value;

        return $n > 0 ? $n : null;
    }

    private static function responseLooksComplete(string $buffer): bool
    {
        $hasDa = (str_contains($buffer, "\033[") && str_ends_with(rtrim($buffer, "\0"), 'c'))
            || (bool) preg_match('/\x1b\[\?[\d;]*c/', $buffer);
        $hasGraphics = str_contains($buffer, "_G") && str_contains($buffer, "\033\\");

        return $hasDa || $hasGraphics;
    }
}
