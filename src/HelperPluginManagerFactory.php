<?php

declare(strict_types=1);

namespace Mezzio\LaminasView;

use Laminas\ServiceManager\Config;
use Laminas\View\HelperPluginManager;
use Psr\Container\ContainerInterface;

class HelperPluginManagerFactory
{
    public function __invoke(ContainerInterface $container): HelperPluginManager
    {
        $manager = new HelperPluginManager($container);

        $config = $container->has('config') ? $container->get('config') : [];
        $config = $config['view_helpers'] ?? [];

        if (! empty($config)) {
            (new Config($config))->configureServiceManager($manager);
        }

        return $manager;
    }
}
