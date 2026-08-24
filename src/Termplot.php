<?php

declare(strict_types=1);

namespace Termplot;

use Termplot\Exception\NotImplementedException;
use Termplot\Fallback\BrailleSparkline;
use Termplot\Fallback\BrailleSparklineInterface;
use Termplot\Fallback\TableFallback;
use Termplot\Fallback\TableFallbackInterface;
use Termplot\Probe\ImageIdAllocator;
use Termplot\Probe\ProbeInterface;
use Termplot\Probe\TerminalProbe;
use Termplot\Protocol\PlacementId;
use Termplot\Render\ChartRendererInterface;
use Termplot\Render\Gd\GdRenderer;
use Termplot\Transmit\KittyTransmitter;
use Termplot\Transmit\TransmitterInterface;

/**
 * Thin facade (D5, A11). Probes, then walks the capability ladder:
 * Kitty → (future backends) → Braille → Table.
 *
 * GD charts (when ext-gd is loaded), then Braille, then table.
 *
 * Sketch: {@code Termplot::line($series)->width(800)->height(240)->draw()}
 */
final class Termplot
{
    private static ?ProbeInterface $probe = null;

    private static ?TransmitterInterface $transmitter = null;

    private static ?ChartRendererInterface $renderer = null;

    private static ?BrailleSparklineInterface $braille = null;

    private static ?TableFallbackInterface $table = null;

    private static ?ImageIdAllocator $ids = null;

    /** @var array<string, int> Reused Kitty image ids per chart kind (live updates). */
    private static array $imageIdsByKind = [];

    private static mixed $input = null;

    private static mixed $output = null;

    /** @var list<int|float> */
    private array $series;

    private string $kind;

    private int $width = 800;

    private int $height = 240;

    /**
     * @param list<int|float> $series
     */
    private function __construct(string $kind, array $series)
    {
        $this->kind = $kind;
        $this->series = $series;
    }

    /**
     * Bind seams for tests / embedding. Not part of the frozen caller sketch.
     */
    public static function bind(
        ?ProbeInterface $probe = null,
        ?TransmitterInterface $transmitter = null,
        ?ChartRendererInterface $renderer = null,
        ?BrailleSparklineInterface $braille = null,
        ?TableFallbackInterface $table = null,
        ?ImageIdAllocator $ids = null,
        mixed $input = null,
        mixed $output = null,
    ): void {
        if ($probe !== null) {
            self::$probe = $probe;
        }
        if ($transmitter !== null) {
            self::$transmitter = $transmitter;
        }
        if ($renderer !== null) {
            self::$renderer = $renderer;
        }
        if ($braille !== null) {
            self::$braille = $braille;
        }
        if ($table !== null) {
            self::$table = $table;
        }
        if ($ids !== null) {
            self::$ids = $ids;
        }
        if ($input !== null) {
            self::$input = $input;
        }
        if ($output !== null) {
            self::$output = $output;
        }
    }

    public static function reset(): void
    {
        self::$probe = null;
        self::$transmitter = null;
        self::$renderer = null;
        self::$braille = null;
        self::$table = null;
        self::$ids = null;
        self::$imageIdsByKind = [];
        self::$input = null;
        self::$output = null;
    }

    /**
     * @param list<int|float> $series
     */
    public static function line(array $series): self
    {
        return new self('line', $series);
    }

    /**
     * @param list<int|float> $series
     */
    public static function bar(array $series): self
    {
        return new self('bar', $series);
    }

    public function width(int $width): self
    {
        if ($width < 1) {
            throw new \InvalidArgumentException('width must be >= 1');
        }
        $this->width = $width;

        return $this;
    }

    public function height(int $height): self
    {
        if ($height < 1) {
            throw new \InvalidArgumentException('height must be >= 1');
        }
        $this->height = $height;

        return $this;
    }

    public function draw(): void
    {
        $input = self::$input ?? (defined('STDIN') ? STDIN : null);
        $output = self::$output ?? (defined('STDOUT') ? STDOUT : null);
        $cap = self::$probe !== null
            ? self::$probe->detect($input, $output)
            : TerminalProbe::detect($input, $output);

        $renderer = self::$renderer ?? new GdRenderer();

        if ($cap->kitty && $renderer->isAvailable()) {
            $bitmap = $this->kind === 'bar'
                ? $renderer->bar($this->series, $this->width, $this->height)
                : $renderer->line($this->series, $this->width, $this->height);
            $ids = self::$ids ??= new ImageIdAllocator();
            $imageId = self::$imageIdsByKind[$this->kind] ??= $ids->next();
            $tx = self::$transmitter ?? new KittyTransmitter(
                $output ?? fopen('php://output', 'w'),
            );
            $tx->replace($bitmap, new PlacementId($imageId, placementId: 1), z: -1, cursorMove: false);

            return;
        }

        $this->drawFallback();
    }

    private function drawFallback(): void
    {
        $out = self::$output ?? (defined('STDOUT') ? STDOUT : fopen('php://output', 'w'));
        $cells = max(8, intdiv($this->width, 20));
        $rows = max(1, intdiv($this->height, 30));
        $braille = self::$braille ?? new BrailleSparkline();
        try {
            $text = $braille->render($this->series, $cells, $rows);
            if ($text !== '') {
                fwrite($out, $text);

                return;
            }
        } catch (NotImplementedException) {
            // Ladder: Braille → Table.
        }

        $table = self::$table ?? new TableFallback();
        fwrite($out, $table->render($this->series));
    }
}
