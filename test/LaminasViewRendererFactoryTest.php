<?php

declare(strict_types=1);

namespace MezzioTest\LaminasView;

use ArrayObject;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\ConfigProvider as ViewConfigProvider;
use Mezzio\LaminasView\ConfigProvider;
use Mezzio\LaminasView\LaminasViewRendererFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use function array_replace_recursive;
use function is_object;
use function iterator_to_array;

/** @psalm-import-type ServiceManagerConfiguration from ServiceManager */
final class LaminasViewRendererFactoryTest extends TestCase
{
    private static function getContainer(iterable $config = []): ContainerInterface
    {
        $serviceConfig = array_replace_recursive(
            (new ViewConfigProvider())->__invoke(),
            (new ConfigProvider())->__invoke(),
            iterator_to_array($config),
        );

        $configService = is_object($config) ? new ArrayObject($serviceConfig) : $serviceConfig;

        /** @psalm-var array{dependencies: ServiceManagerConfiguration} $serviceConfig */
        $serviceConfig['dependencies']['services'] ??= [];

        $serviceConfig['dependencies']['services']['config'] = $configService;
        /** @psalm-var ServiceManagerConfiguration $deps */
        $deps = $serviceConfig['dependencies'] ?? [];

        return new ServiceManager($deps);
    }

    /** @return array<string, array{0: array}> */
    public static function configDataProvider(): array
    {
        return [
            'Layout in templates.layout'            => [
                [
                    'templates' => [
                        'layout' => 'layout',
                        'map'    => [
                            'layout'  => __DIR__ . '/TestAsset/templates/layout/layout.phtml',
                            'content' => __DIR__ . '/TestAsset/templates/namespaced/fred/a.phtml',
                        ],
                    ],
                ],
            ],
            'Layout in templates.default_layout'    => [
                [
                    'templates' => [
                        'default_layout' => 'layout',
                        'map'            => [
                            'layout'  => __DIR__ . '/TestAsset/templates/layout/layout.phtml',
                            'content' => __DIR__ . '/TestAsset/templates/namespaced/fred/a.phtml',
                        ],
                    ],
                ],
            ],
            'Layout in view_manager.default_layout' => [
                [
                    'view_manager' => [
                        'default_layout' => 'layout',
                    ],
                    'templates'    => [
                        'map' => [
                            'layout'  => __DIR__ . '/TestAsset/templates/layout/layout.phtml',
                            'content' => __DIR__ . '/TestAsset/templates/namespaced/fred/a.phtml',
                        ],
                    ],
                ],
            ],
            'templates.layout is preferred'         => [
                [
                    'view_manager' => [
                        'default_layout' => 'alternative',
                    ],
                    'templates'    => [
                        'layout' => 'layout',
                        'map'    => [
                            'alternative' => __DIR__ . '/TestAsset/templates/layout/alternative.phtml',
                            'layout'      => __DIR__ . '/TestAsset/templates/layout/layout.phtml',
                            'content'     => __DIR__ . '/TestAsset/templates/namespaced/fred/a.phtml',
                        ],
                    ],
                ],
            ],
        ];
    }

    /** @return iterable<string, array{0: ArrayObject}> */
    public static function arrayObjectConfigProvider(): iterable
    {
        foreach (self::configDataProvider() as $key => $args) {
            yield $key . ' (ArrayObject)' => [new ArrayObject($args[0])];
        }
    }

    #[DataProvider('configDataProvider')]
    #[DataProvider('arrayObjectConfigProvider')]
    public function testLayoutCanBeSetInMultiplePositions(iterable $config): void
    {
        $container = self::getContainer($config);

        $renderer = (new LaminasViewRendererFactory())->__invoke($container);

        $result = $renderer->render('content');

        self::assertStringContainsString('<layout>', $result);
        self::assertStringContainsString('</layout>', $result);
        self::assertStringContainsString('<h1>Fred A</h1>', $result);
    }
}
