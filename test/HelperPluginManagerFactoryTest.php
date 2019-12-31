<?php

/**
 * @see       https://github.com/mezzio/mezzio-laminasviewrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-laminasviewrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-laminasviewrenderer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\LaminasView;

use Laminas\ServiceManager\ServiceManager;
use Laminas\View\HelperPluginManager;
use Mezzio\LaminasView\HelperPluginManagerFactory;
use MezzioTest\LaminasView\TestAsset\TestHelper;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ProphecyInterface;

class HelperPluginManagerFactoryTest extends TestCase
{
    /**
     * @var ServiceManager|ProphecyInterface
     */
    private $container;

    public function setUp()
    {
        $this->container = $this->prophesize(ServiceManager::class);
    }

    public function testCallingFactoryWithNoConfigReturnsHelperPluginManagerInstance()
    {
        $this->container->has('config')->willReturn(false);
        $factory = new HelperPluginManagerFactory();
        $manager = $factory($this->container->reveal());
        $this->assertInstanceOf(HelperPluginManager::class, $manager);
        return $manager;
    }

    public function testCallingFactoryWithNoViewHelperConfigReturnsHelperPluginManagerInstance()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn([]);
        $factory = new HelperPluginManagerFactory();
        $manager = $factory($this->container->reveal());
        $this->assertInstanceOf(HelperPluginManager::class, $manager);
        return $manager;
    }

    public function testCallingFactoryWithConfigAllowsAddingHelpers()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn(
            [
                'view_helpers' => [
                    'invokables' => [
                        'testHelper' => TestHelper::class,
                    ],
                ],
            ]
        );
        $factory = new HelperPluginManagerFactory();
        $manager = $factory($this->container->reveal());
        $this->assertInstanceOf(HelperPluginManager::class, $manager);
        $this->assertTrue($manager->has('testHelper'));
        $this->assertInstanceOf(TestHelper::class, $manager->get('testHelper'));
        return $manager;
    }
}
