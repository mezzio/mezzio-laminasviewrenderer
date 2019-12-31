<?php

/**
 * @see       https://github.com/mezzio/mezzio-laminasviewrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-laminasviewrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-laminasviewrenderer/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\LaminasView;

use Laminas\ServiceManager\DelegatorFactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class ApplicationUrlDelegatorFactory implements DelegatorFactoryInterface
{
    /**
     * Inject the UrlHelper in an Application instance, when available.
     *
     * @param ServiceLocatorInterface $container
     * @param string $name
     * @param string $requestedName
     * @param callable $callback Callback that returns the Application instance
     */
    public function createDelegatorWithName(ServiceLocatorInterface $container, $name, $requestedName, $callback)
    {
        $application = $callback();
        if ($container->has(UrlHelper::class)) {
            $application->attachRouteResultObserver($container->get(UrlHelper::class));
        }
        return $application;
    }
}
