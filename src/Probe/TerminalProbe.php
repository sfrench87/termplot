<?php

declare(strict_types=1);

namespace Termplot\Probe;

use Termplot\Protocol\KittyEncoder;

/**
 * Detects Kitty graphics capability.
 *
 * 1. Non-TTY → kitty=false (no query, cannot hang).
 * 2. Protocol query preferred (tiny {@code a=q} + Primary DA) with a read deadline.
 * 3. Env heuristics secondary ({@code KITTY_WINDOW_ID}, {@code TERM_PROGRAM}).
 *
 * Frozen sketch: {@code TerminalProbe::detect(STDIN, STDOUT)}
 *
 * Query IO is mockable via {@see ProbeIoInterface}. The injectable
 * {@see ProbeInterface} seam is {@see FakeProbe} (tests) or a thin wrapper around
 * this static entry point (facade). PHP cannot expose both a static and an
 * instance method named {@code detect()} on the same class.
 */
final class TerminalProbe
{
    public const DEFAULT_TIMEOUT_SECONDS = 0.15;

    public function __construct(
        private ProbeIoInterface $io = new PosixProbeIo(),
        private float $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS,
    ) {
        if ($this->timeoutSeconds <= 0) {
            throw new \InvalidArgumentException('Probe timeout must be > 0');
        }
    }

    /**
     * Frozen public API.
     *
     * @param resource|null $input
     * @param resource|null $output
     */
    public static function detect(
        mixed $input = null,
        mixed $output = null,
        ?ProbeIoInterface $io = null,
        float $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS,
    ): Capability {
        return (new self($io ?? new PosixProbeIo(), $timeoutSeconds))->run($input, $output);
    }

    /**
     * @param resource|null $input
     * @param resource|null $output
     */
    public function run(mixed $input = null, mixed $output = null): Capability
    {
        $input ??= defined('STDIN') ? STDIN : null;
        $output ??= defined('STDOUT') ? STDOUT : null;

        $tty = $output !== null && $this->io->isatty($output);
        $size = $output !== null ? $this->io->winsize($output) : [
            'cols' => null,
            'rows' => null,
            'pixelWidth' => null,
            'pixelHeight' => null,
        ];

        if (!$tty) {
            return new Capability(
                kitty: false,
                pixelWidth: $size['pixelWidth'],
                pixelHeight: $size['pixelHeight'],
                cols: $size['cols'],
                rows: $size['rows'],
                tty: false,
            );
        }

        $kitty = null;
        $inputTty = $input !== null && $this->io->isatty($input);
        if ($inputTty && $output !== null && $input !== null) {
            $kitty = $this->protocolQuery($input, $output);
        }

        if ($kitty === null) {
            $kitty = $this->envSaysKitty();
        }

        return new Capability(
            kitty: $kitty,
            pixelWidth: $size['pixelWidth'],
            pixelHeight: $size['pixelHeight'],
            cols: $size['cols'],
            rows: $size['rows'],
            tty: true,
        );
    }

    /**
     * @param resource $input
     * @param resource $output
     */
    private function protocolQuery(mixed $input, mixed $output): ?bool
    {
        $packet = KittyEncoder::encodeQuery(31)[0] . KittyEncoder::PRIMARY_DA;
        $this->io->write($output, $packet);
        $response = $this->io->readWithDeadline($input, $this->timeoutSeconds);

        if ($response === '') {
            return null;
        }

        if (str_contains($response, '_G')) {
            return true;
        }

        if (
            preg_match('/\x1b\[\?[\d;]*c/', $response) === 1
            || (str_contains($response, "\033[") && str_contains($response, 'c'))
        ) {
            return false;
        }

        return null;
    }

    private function envSaysKitty(): bool
    {
        if ($this->io->getenv('KITTY_WINDOW_ID') !== null) {
            return true;
        }

        $termProgram = strtolower((string) $this->io->getenv('TERM_PROGRAM'));
        if (in_array($termProgram, ['kitty', 'ghostty', 'wezterm'], true)) {
            return true;
        }

        $term = strtolower((string) $this->io->getenv('TERM'));

        return str_contains($term, 'kitty') || str_contains($term, 'ghostty');
    }
}
