<?php

declare(strict_types=1);

namespace Termplot\Probe;

/**
 * Canned {@see ProbeInterface} for tests and facade injection.
 */
final class FakeProbe implements ProbeInterface
{
    public function __construct(private Capability $capability)
    {
    }

    public function detect(mixed $input = null, mixed $output = null): Capability
    {
        return $this->capability;
    }
}
