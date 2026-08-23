<?php

declare(strict_types=1);

namespace Termplot\Tests\Unit\Protocol;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Termplot\Protocol\PlacementId;

#[CoversClass(PlacementId::class)]
final class PlacementIdTest extends TestCase
{
    public function testNamedPlacementIdMatchesSketch(): void
    {
        $id = new PlacementId(7, placementId: 1);
        $this->assertSame(7, $id->imageId);
        $this->assertSame(1, $id->placementId);
        $this->assertTrue($id->hasPlacementId());
    }

    public function testZeroPlacementIdMeansUnspecified(): void
    {
        $id = new PlacementId(3);
        $this->assertSame(0, $id->placementId);
        $this->assertFalse($id->hasPlacementId());
    }

    public function testRejectsZeroImageId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PlacementId(0, placementId: 1);
    }
}
