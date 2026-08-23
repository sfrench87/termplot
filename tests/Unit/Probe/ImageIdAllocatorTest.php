<?php

declare(strict_types=1);

namespace Termplot\Tests\Unit\Probe;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Termplot\Probe\ImageIdAllocator;

#[CoversClass(ImageIdAllocator::class)]
final class ImageIdAllocatorTest extends TestCase
{
    public function testAutoAllocateSequence(): void
    {
        $ids = new ImageIdAllocator();
        $this->assertSame(1, $ids->next());
        $this->assertSame(2, $ids->next());
        $this->assertSame(3, $ids->peek());
    }

    public function testCallerOverrideBumpsHighWaterMark(): void
    {
        $ids = new ImageIdAllocator();
        $this->assertSame(99, $ids->next(99));
        $this->assertSame(100, $ids->next());
    }

    public function testLowerOverrideDoesNotRewind(): void
    {
        $ids = new ImageIdAllocator();
        $ids->next();
        $ids->next();
        $this->assertSame(1, $ids->next(1));
        $this->assertSame(3, $ids->next());
    }

    public function testRejectsZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new ImageIdAllocator())->next(0);
    }
}
