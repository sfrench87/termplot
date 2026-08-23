<?php

declare(strict_types=1);

namespace Termplot\Tests\Unit\Probe;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Termplot\Probe\Capability;

#[CoversClass(Capability::class)]
final class CapabilityTest extends TestCase
{
    public function testSchemaFields(): void
    {
        $cap = new Capability(
            kitty: true,
            pixelWidth: 800,
            pixelHeight: 600,
            cols: 80,
            rows: 24,
            tty: true,
        );
        $this->assertTrue($cap->kitty);
        $this->assertSame(800, $cap->pixelWidth);
        $this->assertSame(600, $cap->pixelHeight);
        $this->assertSame(80, $cap->cols);
        $this->assertSame(24, $cap->rows);
        $this->assertTrue($cap->tty);
    }
}
