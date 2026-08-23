<?php

declare(strict_types=1);

namespace Termplot\Tests\Unit\Probe;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Termplot\Probe\FakeProbe;
use Termplot\Probe\FakeProbeIo;
use Termplot\Probe\ProbeInterface;
use Termplot\Probe\TerminalProbe;
use Termplot\Protocol\KittyEncoder;

#[CoversClass(TerminalProbe::class)]
#[CoversClass(FakeProbeIo::class)]
#[CoversClass(FakeProbe::class)]
final class TerminalProbeTest extends TestCase
{
    public function testA5NonTtyIsKittyFalseAndDoesNotHang(): void
    {
        $stream = fopen('php://memory', 'r+');
        $this->assertIsResource($stream);
        $start = microtime(true);
        $cap = TerminalProbe::detect($stream, $stream);
        $elapsed = microtime(true) - $start;
        fclose($stream);

        $this->assertFalse($cap->kitty);
        $this->assertFalse($cap->tty);
        $this->assertLessThan(0.5, $elapsed, 'non-TTY probe must not block on a protocol query');
    }

    public function testNonTtyDoesNotWriteQuery(): void
    {
        $io = new FakeProbeIo(outputTty: false, inputTty: false);
        $cap = TerminalProbe::detect('in', 'out', $io);
        $this->assertFalse($cap->kitty);
        $this->assertSame([], $io->writes);
    }

    public function testA6ProtocolQuerySuccess(): void
    {
        $io = new FakeProbeIo(
            outputTty: true,
            inputTty: true,
            readBuffer: "\033_Gi=31;OK\033\\\033[?62;c",
        );
        $cap = TerminalProbe::detect('in', 'out', $io);
        $this->assertTrue($cap->kitty);
        $this->assertTrue($cap->tty);
        $this->assertCount(1, $io->writes);
        $this->assertStringContainsString('a=q', $io->writes[0]);
        $this->assertStringEndsWith(KittyEncoder::PRIMARY_DA, $io->writes[0]);
    }

    public function testProtocolQueryDaWithoutGraphicsIsNotKitty(): void
    {
        $io = new FakeProbeIo(
            outputTty: true,
            inputTty: true,
            readBuffer: "\033[?1;2c",
        );
        $cap = TerminalProbe::detect('in', 'out', $io);
        $this->assertFalse($cap->kitty);
        $this->assertNotEmpty($io->writes);
    }

    public function testBareGSubstringIsNotAGraphicsReply(): void
    {
        $io = new FakeProbeIo(
            outputTty: true,
            inputTty: true,
            readBuffer: "noise_G_noise\033[?1;2c",
        );
        $cap = TerminalProbe::detect('in', 'out', $io);
        $this->assertFalse($cap->kitty);
    }

    public function testEnvHeuristicWhenQueryInconclusive(): void
    {
        $io = new FakeProbeIo(
            outputTty: true,
            inputTty: true,
            readBuffer: '',
            env: ['KITTY_WINDOW_ID' => '1'],
        );
        $cap = TerminalProbe::detect('in', 'out', $io, 0.05);
        $this->assertTrue($cap->kitty);
    }

    public function testGhosttyTermProgram(): void
    {
        $io = new FakeProbeIo(
            outputTty: true,
            inputTty: false,
            env: ['TERM_PROGRAM' => 'ghostty'],
        );
        $cap = TerminalProbe::detect('in', 'out', $io);
        $this->assertTrue($cap->kitty);
        $this->assertSame([], $io->writes, 'no readable TTY stdin → skip protocol query');
    }

    public function testFakeProbeIsInjectable(): void
    {
        $probe = new FakeProbe(new \Termplot\Probe\Capability(true, 1, 1, 80, 24, true));
        $this->assertInstanceOf(ProbeInterface::class, $probe);
        $this->assertTrue($probe->detect()->kitty);
    }
}
