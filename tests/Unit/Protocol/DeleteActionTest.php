<?php

declare(strict_types=1);

namespace Termplot\Tests\Unit\Protocol;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Termplot\Protocol\DeleteAction;

#[CoversClass(DeleteAction::class)]
final class DeleteActionTest extends TestCase
{
    public function testAllVisible(): void
    {
        $this->assertSame(['d' => 'a'], DeleteAction::all()->controlKeys());
        $this->assertSame(['d' => 'A'], DeleteAction::all(freeData: true)->controlKeys());
    }

    public function testByImageAndPlacement(): void
    {
        $keys = DeleteAction::byImage(10, placementId: 7)->controlKeys();
        $this->assertSame(['d' => 'i', 'i' => '10', 'p' => '7'], $keys);
    }

    public function testByZ(): void
    {
        $keys = DeleteAction::byZ(-1, freeData: true)->controlKeys();
        $this->assertSame(['d' => 'Z', 'z' => '-1'], $keys);
    }
}
