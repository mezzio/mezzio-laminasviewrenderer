<?php

declare(strict_types=1);

namespace MezzioTest\LaminasView;

use Laminas\ServiceManager\ServiceManager;
use Laminas\View\ConfigProvider as ViewConfigProvider;
use Laminas\View\HelperPluginManagerInterface;
use Mezzio\LaminasView\ConfigProvider;
use Mezzio\LaminasView\ServerUrlHelper;
use Mezzio\LaminasView\UrlHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use function array_replace_recursive;

/** @psalm-import-type ServiceManagerConfiguration from ServiceManager */
final class ViewHelperIntegrationTest extends TestCase
{
    private static function getContainer(): ContainerInterface
    {
        $serviceConfig = array_replace_recursive(
            (new ViewConfigProvider())->__invoke(),
            (new ConfigProvider())->__invoke(),
            (new \Mezzio\Helper\ConfigProvider())->__invoke(),
            (new \Mezzio\Router\ConfigProvider())->__invoke(),
            (new \Mezzio\Router\FastRouteRouter\ConfigProvider())->__invoke(),
        );

        /** @psalm-var array{dependencies: ServiceManagerConfiguration} $serviceConfig */
        $serviceConfig['dependencies']['services'] ??= [];

        $serviceConfig['dependencies']['services']['config'] = $serviceConfig;
        /** @psalm-var ServiceManagerConfiguration $deps */
        $deps = $serviceConfig['dependencies'] ?? [];

        return new ServiceManager($deps);
    }

    /** @return array<string, array{0: string, 1: class-string}> */
    public static function helperProvider(): array
    {
        return [
            UrlHelper::class       => [UrlHelper::class, UrlHelper::class],
            'url'                  => ['url', UrlHelper::class],
            ServerUrlHelper::class => [ServerUrlHelper::class, ServerUrlHelper::class],
            'serverUrl'            => ['serverUrl', ServerUrlHelper::class],
        ];
    }

    /** @param class-string $expect */
    #[DataProvider('helperProvider')]
    public function testHelpersCanBeRetrieved(string $id, string $expect): void
    {
        $container = self::getContainer();
        $helpers   = $container->get(HelperPluginManagerInterface::class);

        /** @psalm-var mixed $helper */
        $helper = $helpers->get($id);

        self::assertInstanceOf($expect, $helper);
    }
}
