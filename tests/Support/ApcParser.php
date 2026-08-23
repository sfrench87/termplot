<?php

declare(strict_types=1);

namespace Termplot\Tests\Support;

/**
 * Parse a Kitty APC frame into control keys + payload.
 *
 * @phpstan-type Frame array{keys: array<string, string>, payload: string}
 */
final class ApcParser
{
    /**
     * @return array{keys: array<string, string>, payload: string}
     */
    public static function parse(string $frame): array
    {
        if (!str_starts_with($frame, "\033_G") || !str_ends_with($frame, "\033\\")) {
            throw new \InvalidArgumentException('Not a Kitty APC frame');
        }

        $inner = substr($frame, 3, -2);
        $semi = strpos($inner, ';');
        $control = $semi === false ? $inner : substr($inner, 0, $semi);
        $payload = $semi === false ? '' : substr($inner, $semi + 1);

        $keys = [];
        if ($control !== '') {
            foreach (explode(',', $control) as $pair) {
                $parts = explode('=', $pair, 2);
                if (count($parts) !== 2) {
                    throw new \InvalidArgumentException('Malformed control pair: ' . $pair);
                }
                $keys[$parts[0]] = $parts[1];
            }
        }

        return ['keys' => $keys, 'payload' => $payload];
    }

    /**
     * @return list<array{keys: array<string, string>, payload: string}>
     */
    public static function parseAll(string $concatenated): array
    {
        $frames = [];
        $offset = 0;
        while (($start = strpos($concatenated, "\033_G", $offset)) !== false) {
            $end = strpos($concatenated, "\033\\", $start + 3);
            if ($end === false) {
                throw new \InvalidArgumentException('Unterminated APC frame');
            }
            $frames[] = self::parse(substr($concatenated, $start, $end + 2 - $start));
            $offset = $end + 2;
        }

        return $frames;
    }
}
