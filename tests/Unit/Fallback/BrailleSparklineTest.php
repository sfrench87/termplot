<?php

declare(strict_types=1);

namespace Termplot\Tests\Unit\Fallback;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Termplot\Fallback\BrailleSparkline;

#[CoversClass(BrailleSparkline::class)]
final class BrailleSparklineTest extends TestCase
{
    public function testFromValuesRenderIsNonEmptyWithoutApc(): void
    {
        $out = BrailleSparkline::fromValues([1, 3, 2, 5, 4, 6])->render();
        $this->assertNotSame('', $out);
        $this->assertStringNotContainsString("\033_G", $out);
        $this->assertStringNotContainsString("\033[", $out);
        $this->assertMatchesRegularExpression('/[\x{2800}-\x{28FF}]/u', $out);
    }

    public function testInterfaceRenderMatchesStoredValues(): void
    {
        $a = BrailleSparkline::fromValues([0, 1, 2, 3, 4], 24, 4)->render();
        $b = (new BrailleSparkline())->render([0, 1, 2, 3, 4], 24, 4);
        $this->assertSame($a, $b);
    }

    public function testDifferentSeriesDiffer(): void
    {
        $up = BrailleSparkline::fromValues([0, 0, 0, 1, 1, 1], 16, 4)->render();
        $down = BrailleSparkline::fromValues([1, 1, 1, 0, 0, 0], 16, 4)->render();
        $this->assertNotSame($up, $down);
    }

    public function testEmptySeriesStillNonEmpty(): void
    {
        $out = BrailleSparkline::fromValues([])->render();
        $this->assertNotSame('', $out);
        $this->assertStringNotContainsString("\033_G", $out);
    }
}
