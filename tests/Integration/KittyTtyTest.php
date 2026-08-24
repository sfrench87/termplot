<?php

declare(strict_types=1);

namespace Termplot\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Termplot\Probe\TerminalProbe;
use Termplot\Protocol\PlacementId;
use Termplot\Render\Bitmap;
use Termplot\Tests\Support\TinyPng;
use Termplot\Transmit\KittyTransmitter;

/**
 * Real-TTY checks. Skipped unless KITTY_TEST=1 so CI never hangs or needs a terminal.
 *
 * @group tty
 */
#[Group('tty')]
#[CoversClass(KittyTransmitter::class)]
final class KittyTtyTest extends TestCase
{
    protected function setUp(): void
    {
        if (getenv('KITTY_TEST') !== '1') {
            $this->markTestSkipped('Integration TTY tests require KITTY_TEST=1');
        }
        if (!defined('STDOUT') || !function_exists('stream_isatty') || !stream_isatty(STDOUT)) {
            $this->markTestSkipped('STDOUT is not a TTY');
        }
    }

    public function testDetectAndReplaceOnLiveTty(): void
    {
        $cap = TerminalProbe::detect(STDIN, STDOUT);
        $this->assertIsBool($cap->kitty);
        $this->assertTrue($cap->tty);

        if (!$cap->kitty) {
            $this->markTestSkipped('Live TTY did not report Kitty graphics');
        }

        $tx = new KittyTransmitter(STDOUT);
        $tx->replace(
            Bitmap::png(TinyPng::bytes()),
            new PlacementId(1, placementId: 1),
            z: -1,
            cursorMove: false,
        );
        $this->assertTrue(true);
    }
}
