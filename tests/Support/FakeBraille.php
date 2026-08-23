<?php

declare(strict_types=1);

namespace Termplot\Tests\Support;

use Termplot\Fallback\BrailleSparklineInterface;

final class FakeBraille implements BrailleSparklineInterface
{
    public function __construct(private string $output = "▁▂▃")
    {
    }

    public function render(array $series = [], int $width = 40, int $height = 8): string
    {
        return $this->output;
    }
}
