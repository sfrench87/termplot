<?php

declare(strict_types=1);

namespace Termplot\Tests\Unit\Fallback;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Termplot\Fallback\TableFallback;

#[CoversClass(TableFallback::class)]
final class TableFallbackTest extends TestCase
{
    public function testRenderIncludesValuesAndAsciiBarWithoutApc(): void
    {
        $out = (new TableFallback())->render([1.5, 3.0, 2.25]);
        $this->assertNotSame('', $out);
        $this->assertStringContainsString('1.5', $out);
        $this->assertStringContainsString('3', $out);
        $this->assertStringContainsString('#', $out);
        $this->assertStringNotContainsString("\033_G", $out);
        $this->assertStringNotContainsString("\033[", $out);
    }

    public function testEmptySeriesHasHeader(): void
    {
        $out = (new TableFallback())->render([]);
        $this->assertStringContainsString('idx', $out);
        $this->assertStringContainsString('value', $out);
        $this->assertStringNotContainsString("\033_G", $out);
    }
}
