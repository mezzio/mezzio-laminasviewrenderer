<?php

/**
 * @see       https://github.com/mezzio/mezzio-laminasviewrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-laminasviewrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-laminasviewrenderer/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\LaminasView\Exception;

use DomainException;
use Interop\Container\Exception\ContainerException;

class MissingHelperException extends DomainException implements
    ContainerException,
    ExceptionInterface
{
}
