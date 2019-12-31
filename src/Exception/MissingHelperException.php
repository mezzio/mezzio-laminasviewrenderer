<?php

/**
 * @see       https://github.com/mezzio/mezzio-laminasviewrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-laminasviewrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-laminasviewrenderer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\LaminasView\Exception;

use DomainException;
use Psr\Container\ContainerExceptionInterface;

class MissingHelperException extends DomainException implements
    ContainerExceptionInterface,
    ExceptionInterface
{
}
