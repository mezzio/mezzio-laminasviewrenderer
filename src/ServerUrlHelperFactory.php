<?php

declare(strict_types=1);

namespace Mezzio\LaminasView;

use Mezzio\Helper\ServerUrlHelper as MezzioServerUrlHelper;
use Psr\Container\ContainerInterface;

/**
 * @psalm-internal Mezzio\LaminasView
 * @psalm-internal MezzioTest\LaminasView
 */
final class ServerUrlHelperFactory
{
    public function __invoke(ContainerInterface $container): ServerUrlHelper
    {
        return new ServerUrlHelper(
            $container->get(MezzioServerUrlHelper::class),
        );
    }
}
