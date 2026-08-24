<?php

declare(strict_types=1);

namespace Termplot\Protocol;

use Termplot\Exception\ProtocolException;
use Termplot\Render\Bitmap;
use Termplot\Transmit\TransmitOptions;

/**
 * Pure Kitty graphics encoder: Bitmap + Placement + options → APC frame strings.
 *
 * Format: {@code \033_G} … {@code \033\\}
 */
final class KittyEncoder
{
    public const APC_INTRODUCER = "\033_G";
    public const ST = "\033\\";
    public const PRIMARY_DA = "\033[c";

    /** Canonical key order for stable golden fixtures. */
    private const KEY_ORDER = [
        'a', 'd', 'f', 't', 's', 'v', 'o', 'i', 'p', 'c', 'r', 'z', 'C', 'q', 'N', 'm', 'x', 'y',
    ];

    /**
     * Encode transmit / transmit+display. Chunks base64 at 4096.
     *
     * @return list<string>
     */
    public static function encode(Bitmap $bitmap, PlacementId $placement, TransmitOptions $options): array
    {
        $payload = $bitmap->bytes;
        if ($options->compress) {
            if (!function_exists('gzcompress')) {
                throw new ProtocolException('o=z compression requires ext-zlib');
            }
            $compressed = gzcompress($payload);
            if ($compressed === false) {
                throw new ProtocolException('zlib compression failed');
            }
            $payload = $compressed;
        }

        $b64 = base64_encode($payload);
        $chunks = Chunker::split($b64);
        $multi = count($chunks) > 1;

        $frames = [];
        $last = count($chunks) - 1;
        foreach ($chunks as $i => $chunk) {
            if ($i === 0) {
                $keys = self::transmitKeys($bitmap, $placement, $options);
                if ($multi) {
                    $keys['m'] = '1';
                }
            } else {
                $keys = ['m' => $i === $last ? '0' : '1'];
                if ($options->quiet !== TransmitOptions::QUIET_NONE) {
                    $keys['q'] = (string) $options->quiet;
                }
            }
            $frames[] = self::frame($keys, $chunk);
        }

        return $frames;
    }

    /**
     * Encode a place-only command ({@code a=p}), no pixel payload.
     *
     * @return list<string>
     */
    public static function encodePlace(PlacementId $placement, TransmitOptions $options): array
    {
        $keys = [
            'a' => TransmitOptions::ACTION_PLACE,
            'i' => (string) $placement->imageId,
        ];
        if ($placement->hasPlacementId()) {
            $keys['p'] = (string) $placement->placementId;
        }
        self::addPlacementKeys($keys, $options);

        return [self::frame($keys, '')];
    }

    /**
     * Encode {@code a=d} delete. Never a delete-first step of replace().
     *
     * @return list<string>
     */
    public static function encodeDelete(DeleteAction $action): array
    {
        $keys = ['a' => TransmitOptions::ACTION_DELETE] + $action->controlKeys();

        return [self::frame($keys, '')];
    }

    /**
     * Tiny protocol query: 1×1 RGB ({@code AAAA}) with {@code a=q}.
     *
     * Pair with {@see PRIMARY_DA} when probing a real TTY.
     *
     * @return list<string>
     */
    public static function encodeQuery(int $imageId = 1): array
    {
        if ($imageId < PlacementId::MIN_IMAGE_ID || $imageId > PlacementId::MAX_ID) {
            throw new \InvalidArgumentException('imageId must be in 1..4294967295');
        }

        return [self::frame([
            'a' => TransmitOptions::ACTION_QUERY,
            't' => 'd',
            'f' => (string) Bitmap::FORMAT_RGB,
            's' => '1',
            'v' => '1',
            'i' => (string) $imageId,
        ], 'AAAA')];
    }

    /**
     * @param array<string, string> $keys
     */
    public static function frame(array $keys, string $payload): string
    {
        $ordered = [];
        foreach (self::KEY_ORDER as $key) {
            if (array_key_exists($key, $keys) && $keys[$key] !== '') {
                $ordered[] = $key . '=' . $keys[$key];
            }
        }
        foreach ($keys as $key => $value) {
            if (!in_array($key, self::KEY_ORDER, true) && $value !== '') {
                $ordered[] = $key . '=' . $value;
            }
        }

        $control = implode(',', $ordered);
        $body = $payload === '' ? $control : $control . ';' . $payload;

        return self::APC_INTRODUCER . $body . self::ST;
    }

    /**
     * @return array<string, string>
     */
    private static function transmitKeys(Bitmap $bitmap, PlacementId $placement, TransmitOptions $options): array
    {
        $keys = [
            'a' => $options->action,
            'f' => (string) $bitmap->format,
            't' => 'd',
            'i' => (string) $placement->imageId,
        ];

        if ($bitmap->format !== Bitmap::FORMAT_PNG) {
            if ($bitmap->width === null || $bitmap->height === null) {
                throw new ProtocolException('RGB/RGBA bitmaps require width and height (s=, v=)');
            }
            $keys['s'] = (string) $bitmap->width;
            $keys['v'] = (string) $bitmap->height;
        }

        if ($options->compress) {
            $keys['o'] = 'z';
        }

        $places = $options->action === TransmitOptions::ACTION_TRANSMIT_AND_DISPLAY
            || $options->action === TransmitOptions::ACTION_PLACE;

        if ($places && $placement->hasPlacementId()) {
            $keys['p'] = (string) $placement->placementId;
        }

        if ($places) {
            self::addPlacementKeys($keys, $options);
        } elseif ($options->quiet !== TransmitOptions::QUIET_NONE) {
            $keys['q'] = (string) $options->quiet;
        }

        if ($options->transient) {
            $keys['N'] = '1';
        }

        return $keys;
    }

    /**
     * @param array<string, string> $keys
     */
    private static function addPlacementKeys(array &$keys, TransmitOptions $options): void
    {
        if ($options->columns !== null) {
            $keys['c'] = (string) $options->columns;
        }
        if ($options->rows !== null) {
            $keys['r'] = (string) $options->rows;
        }
        $keys['z'] = (string) $options->z;
        if (!$options->cursorMove) {
            $keys['C'] = '1';
        }
        if ($options->quiet !== TransmitOptions::QUIET_NONE) {
            $keys['q'] = (string) $options->quiet;
        }
    }
}
