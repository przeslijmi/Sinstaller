<?php

declare(strict_types=1);

namespace Przeslijmi\Sinstaller;

use Exception as PhpException;
use Throwable;

/**
 * Critical exception that should be catched only outside the installer.
 */
class CriticalException extends PhpException
{
}
