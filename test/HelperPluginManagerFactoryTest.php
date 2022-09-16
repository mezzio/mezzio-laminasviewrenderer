<?php

declare(strict_types=1);

namespace MezzioTest\LaminasView;

use Laminas\View\HelperPluginManager;
use Mezzio\LaminasView\HelperPluginManagerFactory;
use MezzioTest\LaminasView\TestAsset\TestHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class HelperPluginManagerFactoryTest extends TestCase
{
    /** @var ContainerInterface&MockObject */
    private ContainerInterface $container;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function testCallingFactoryWithNoConfigReturnsHelperPluginManagerInstance(): HelperPluginManager
    {
        $this->container
            ->expects(self::once())
            ->method('has')
            ->with('config')
            ->willReturn(false);

        $this->container->expects(self::never())->method('get');

        $factory = new HelperPluginManagerFactory();

        return $factory($this->container);
    }

    /**
     * @psalm-param array<array-key, mixed> $configuration
     */
    private function containerWillHaveConfiguration(array $configuration): void
    {
        $this->container
            ->expects(self::once())
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $this->container
            ->expects(self::once())
            ->method('get')
            ->with('config')
            ->willReturn($configuration);
    }

    public function testCallingFactoryWithNoViewHelperConfigReturnsHelperPluginManagerInstance(): HelperPluginManager
    {
        $this->containerWillHaveConfiguration([]);

        $factory = new HelperPluginManagerFactory();

        return $factory($this->container);
    }

    public function testCallingFactoryWithConfigAllowsAddingHelpers(): void
    {
        $this->containerWillHaveConfiguration(
            [
                'view_helpers' => [
                    'invokables' => [
                        'testHelper' => TestHelper::class,
                    ],
                ],
            ]
        );

        $factory = new HelperPluginManagerFactory();
        $manager = $factory($this->container);

        $this->assertTrue($manager->has('testHelper'));
        $this->assertInstanceOf(TestHelper::class, $manager->get('testHelper'));
    }
}
