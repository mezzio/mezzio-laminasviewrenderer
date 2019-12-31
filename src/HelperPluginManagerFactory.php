<?php

/**
 * @see       https://github.com/mezzio/mezzio-laminasviewrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-laminasviewrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-laminasviewrenderer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\LaminasView;

use Laminas\ServiceManager\Config;
use Laminas\View\HelperPluginManager;
use Psr\Container\ContainerInterface;

class HelperPluginManagerFactory
{
    public function __invoke(ContainerInterface $container) : HelperPluginManager
    {
        $manager = new HelperPluginManager($container);

        $config = $container->has('config') ? $container->get('config') : [];
        $config = isset($config['view_helpers']) ? $config['view_helpers'] : [];

        if (! empty($config)) {
            (new Config($config))->configureServiceManager($manager);
        }

        return $manager;
    }
}
