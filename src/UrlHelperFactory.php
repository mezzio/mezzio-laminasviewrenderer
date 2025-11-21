<?php

declare(strict_types=1);

namespace Mezzio\LaminasView;

use Mezzio\Helper\UrlHelperInterface;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @psalm-internal Mezzio\LaminasView
 * @psalm-internal MezzioTest\LaminasView
 */
final class UrlHelperFactory
{
    public function __invoke(ContainerInterface $container): UrlHelper
    {
        return new UrlHelper(
            $container->get(UrlHelperInterface::class),
        );
    }
}
