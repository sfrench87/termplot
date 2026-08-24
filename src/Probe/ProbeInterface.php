<?php

declare(strict_types=1);

namespace Termplot\Probe;

/**
 * Injectable capability probe (fakeable for tests; no TTY required).
 */
interface ProbeInterface
{
    /**
     * @param resource|null $input
     * @param resource|null $output
     */
    public function detect(mixed $input = null, mixed $output = null): Capability;
}
