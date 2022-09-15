<?php

declare(strict_types=1);

namespace Mezzio\LaminasView;

use ArrayAccess;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\ConfigInterface;
use Laminas\View\HelperPluginManager;
use Psr\Container\ContainerInterface;

use function assert;
use function is_array;

/** @psalm-import-type ServiceManagerConfigurationType from ConfigInterface */
class HelperPluginManagerFactory
{
    public function __invoke(ContainerInterface $container): HelperPluginManager
    {
        $manager = new HelperPluginManager($container);

        $config = $container->has('config') ? $container->get('config') : [];
        assert(is_array($config) || $config instanceof ArrayAccess);
        /** @psalm-var ServiceManagerConfigurationType $helperConfig */
        $helperConfig = $config['view_helpers'] ?? [];

        if (! empty($helperConfig)) {
            (new Config($helperConfig))->configureServiceManager($manager);
        }

        return $manager;
    }
}
