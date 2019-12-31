<?php

/**
 * @see       https://github.com/mezzio/mezzio-laminasviewrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-laminasviewrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-laminasviewrenderer/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\LaminasView;

use Laminas\ServiceManager\ServiceLocatorInterface;
use Mezzio\Application;
use Mezzio\LaminasView\ApplicationUrlDelegatorFactory;
use Mezzio\LaminasView\UrlHelper;
use PHPUnit_Framework_TestCase as TestCase;

class ApplicationUrlDelegatorFactoryTest extends TestCase
{
    public function testDelegatorRegistersUrlHelperAsRouteResultObserverWithApplication()
    {
        $urlHelper = $this->prophesize(UrlHelper::class);
        $application = $this->prophesize(Application::class);
        $application->attachRouteResultObserver($urlHelper->reveal())->shouldBeCalled();
        $applicationCallback = function () use ($application) {
            return $application->reveal();
        };

        $container = $this->prophesize(ServiceLocatorInterface::class);
        $container->has(UrlHelper::class)->willReturn(true);
        $container->get(UrlHelper::class)->willReturn($urlHelper->reveal());

        $delegator = new ApplicationUrlDelegatorFactory();
        $test = $delegator->createDelegatorWithName(
            $container->reveal(),
            Application::class,
            Application::class,
            $applicationCallback
        );
        $this->assertSame($application->reveal(), $test);
    }
}
