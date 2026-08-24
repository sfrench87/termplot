<?php

declare(strict_types=1);

namespace Termplot\Exception;

/**
 * Thrown when a GD chart is asked to render without {@code ext-gd}.
 */
final class RendererUnavailableException extends TermplotException
{
}
