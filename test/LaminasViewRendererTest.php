<?php

declare(strict_types=1);

namespace MezzioTest\LaminasView;

use ArrayObject;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\ConfigProvider as ViewConfigProvider;
use Laminas\View\Model\ViewModel;
use Laminas\View\View;
use Mezzio\LaminasView\ConfigProvider;
use Mezzio\LaminasView\LaminasViewRenderer;
use Mezzio\Template\Exception\InvalidArgumentException;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use function array_replace_recursive;
use function uniqid;

/** @psalm-import-type ServiceManagerConfiguration from ServiceManager */
final class LaminasViewRendererTest extends TestCase
{
    private static function getContainer(array $config = []): ContainerInterface
    {
        $serviceConfig = array_replace_recursive(
            (new ViewConfigProvider())->__invoke(),
            (new ConfigProvider())->__invoke(),
            $config,
        );

        /** @psalm-var array{dependencies: ServiceManagerConfiguration} $serviceConfig */
        $serviceConfig['dependencies']['services'] ??= [];

        $serviceConfig['dependencies']['services']['config'] = $serviceConfig;
        /** @psalm-var ServiceManagerConfiguration $deps */
        $deps = $serviceConfig['dependencies'] ?? [];

        return new ServiceManager($deps);
    }

    private static function rendererWithConfig(array $config = []): LaminasViewRenderer
    {
        return self::getContainer($config)->get(LaminasViewRenderer::class);
    }

    public function testLayoutCannotBeAnEmptyString(): void
    {
        $container = self::getContainer();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Layout must be a non-empty-string');

        new LaminasViewRenderer(
            $container->get(View::class),
            '',
        );
    }

    public function testRenderTemplateWithDefaultLayout(): void
    {
        $renderer = self::rendererWithConfig([
            'templates' => [
                'map'    => [
                    'layout' => __DIR__ . '/TestAsset/templates/layout/layout.phtml',
                ],
                'paths'  => [
                    'foo' => __DIR__ . '/TestAsset/templates/namespaced/fred',
                ],
                'layout' => 'layout',
            ],
        ]);

        $markup = $renderer->render('foo::a');

        self::assertStringContainsString('<layout>', $markup);
        self::assertStringContainsString('</layout>', $markup);
        self::assertStringContainsString('<h1>Fred A</h1>', $markup);
    }

    public function testLayoutIsSkippedWhenLayoutIsFalse(): void
    {
        $renderer = self::rendererWithConfig([
            'templates' => [
                'map'    => [
                    'layout' => __DIR__ . '/TestAsset/templates/layout/layout.phtml',
                ],
                'paths'  => [
                    'foo' => __DIR__ . '/TestAsset/templates/namespaced/fred',
                ],
                'layout' => 'layout',
            ],
        ]);

        $markup = $renderer->render('foo::a', ['layout' => false]);

        self::assertStringNotContainsString('<layout>', $markup);
        self::assertStringNotContainsString('</layout>', $markup);
        self::assertStringContainsString('<h1>Fred A</h1>', $markup);
    }

    /** @return array<array-key, array<array-key, mixed>> */
    public static function invalidParameterValues(): array
    {
        return [
            'true'       => [true],
            'false'      => [false],
            'zero'       => [0],
            'int'        => [1],
            'zero-float' => [0.0],
            'float'      => [1.1],
            'string'     => ['value'],
        ];
    }

    #[DataProvider('invalidParameterValues')]
    public function testRenderRaisesExceptionForInvalidParameterTypes(mixed $params): void
    {
        $renderer = $this->rendererWithConfig();
        $this->expectException(InvalidArgumentException::class);

        /** @psalm-suppress MixedArgument */
        $renderer->render('foo', $params);
    }

    public function testCanRenderWithNullParams(): void
    {
        $renderer = $this->rendererWithConfig([
            'templates' => [
                'map' => [
                    'test::a' => __DIR__ . '/TestAsset/templates/namespaced/fred/a.phtml',
                ],
            ],
        ]);

        $result = $renderer->render('test::a', null);
        $expect = <<<HTML
            <h1>Fred A</h1>

            HTML;
        $this->assertSame($expect, $result);
    }

    /** @return array<string, array{0: object, 1: string}> */
    public static function objectParameterValues(): array
    {
        $names = [
            'stdClass'    => uniqid('', false),
            'ArrayObject' => uniqid('', false),
        ];

        return [
            'stdClass'    => [(object) ['name' => $names['stdClass']], $names['stdClass']],
            'ArrayObject' => [new ArrayObject(['name' => $names['ArrayObject']]), $names['ArrayObject']],
        ];
    }

    #[DataProvider('objectParameterValues')]
    public function testCanRenderWithParameterObjects(object $params, string $search): void
    {
        $renderer = $this->rendererWithConfig([
            'templates' => [
                'paths' => [
                    __DIR__ . '/TestAsset/templates',
                ],
            ],
        ]);
        $result   = $renderer->render('object-name', $params);
        $this->assertStringContainsString($search, $result);
    }

    public function testSharedDefaultParameterIsAvailableInLayout(): void
    {
        $renderer = $this->rendererWithConfig([
            'templates' => [
                'layout' => 'layout',
                'map'    => [
                    'layout'  => __DIR__ . '/TestAsset/templates/layout/global-param.phtml',
                    'content' => __DIR__ . '/TestAsset/templates/namespaced/fred/a.phtml',
                ],
            ],
        ]);

        $renderer->addDefaultParam($renderer::TEMPLATE_ALL, 'global', 'GLOBAL PARAM');
        $result = $renderer->render('content');

        $this->assertStringContainsString('GLOBAL PARAM', $result);
    }

    public function testTemplateDefaultParameterIsNotAvailableInLayout(): void
    {
        $renderer = $this->rendererWithConfig([
            'templates' => [
                'layout' => 'layout',
                'map'    => [
                    'layout'  => __DIR__ . '/TestAsset/templates/layout/global-param.phtml',
                    'content' => __DIR__ . '/TestAsset/templates/default-param.phtml',
                ],
            ],
        ]);

        $renderer->addDefaultParam(TemplateRendererInterface::TEMPLATE_ALL, 'global', 'FOZZY BEAR');
        $renderer->addDefaultParam('content', 'global', 'KERMIT');

        $result = $renderer->render('content');

        self::assertStringContainsString('<h1>FOZZY BEAR</h1>', $result);
        self::assertStringContainsString('<content>KERMIT</content>', $result);
    }

    public function testLayoutTemplateDefaultParameterIsAvailableInLayout(): void
    {
        $renderer = $this->rendererWithConfig([
            'templates' => [
                'layout' => 'layout',
                'map'    => [
                    'layout'  => __DIR__ . '/TestAsset/templates/layout/global-param.phtml',
                    'content' => __DIR__ . '/TestAsset/templates/namespaced/fred/a.phtml',
                ],
            ],
        ]);

        $renderer->addDefaultParam('layout', 'global', 'MISS PIGGY');

        $result = $renderer->render('content');

        self::assertStringContainsString('<h1>MISS PIGGY</h1>', $result);
        self::assertStringContainsString('<h1>Fred A</h1>', $result);
    }

    public function testVariableInProvidedLayoutViewModelOverridesTemplateDefaultParameter(): void
    {
        $renderer = $this->rendererWithConfig([
            'templates' => [
                'layout' => 'layout',
                'map'    => [
                    'layout'  => __DIR__ . '/TestAsset/templates/layout/global-param.phtml',
                    'content' => __DIR__ . '/TestAsset/templates/default-param.phtml',
                ],
            ],
        ]);

        $renderer->addDefaultParam('layout', 'global', 'GLOBAL DEFAULT');
        $layout = new ViewModel(['global' => 'LAYOUT SPECIFIC'], 'layout');

        $result = $renderer->render('content', ['global' => 'CONTENT SPECIFIC', 'layout' => $layout]);

        $this->assertStringNotContainsString('GLOBAL DEFAULT', $result);
        $this->assertStringContainsString('<h1>LAYOUT SPECIFIC</h1>', $result);
        $this->assertStringContainsString('<content>CONTENT SPECIFIC</content>', $result);
    }

    public function testLayoutCanBeChangedViaLayoutVariable(): void
    {
        $renderer = $this->rendererWithConfig([
            'templates' => [
                'layout' => 'layout1',
                'map'    => [
                    'layout1' => __DIR__ . '/TestAsset/templates/layout/global-param.phtml',
                    'layout2' => __DIR__ . '/TestAsset/templates/layout/layout.phtml',
                    'content' => __DIR__ . '/TestAsset/templates/namespaced/fred/a.phtml',
                ],
            ],
        ]);

        $result = $renderer->render('content', ['layout' => 'layout2']);

        self::assertStringContainsString('<layout><h1>Fred A</h1>', $result);
    }

    public function testCanPassViewModelForLayoutToConstructor(): void
    {
        $container = self::getContainer([
            'templates' => [
                'map' => [
                    'layout'  => __DIR__ . '/TestAsset/templates/layout/layout.phtml',
                    'content' => __DIR__ . '/TestAsset/templates/namespaced/fred/a.phtml',
                ],
            ],
        ]);

        $layout = new ViewModel([], 'layout');

        $renderer = new LaminasViewRenderer(
            $container->get(View::class),
            $layout,
        );

        $result = $renderer->render('content');

        self::assertStringContainsString('<layout><h1>Fred A</h1>', $result);
    }

    public function testDisableLayoutViaDefaultParameter(): void
    {
        $renderer = $this->rendererWithConfig([
            'templates' => [
                'layout' => 'layout',
                'map'    => [
                    'layout'  => __DIR__ . '/TestAsset/templates/layout/layout.phtml',
                    'content' => __DIR__ . '/TestAsset/templates/namespaced/fred/a.phtml',
                ],
            ],
        ]);

        $renderer->addDefaultParam(TemplateRendererInterface::TEMPLATE_ALL, 'layout', false);

        $result = $renderer->render('content');

        self::assertStringNotContainsString('<layout>', $result);
        self::assertStringContainsString('<h1>Fred A</h1>', $result);
    }

    public function testProperlyResolvesNamespacedTemplate(): void
    {
        $renderer = $this->rendererWithConfig([
            'templates' => [
                'layout' => 'layout',
                'map'    => [
                    'layout' => __DIR__ . '/TestAsset/templates/layout/layout.phtml',
                ],
                'paths'  => [
                    'ns' => __DIR__ . '/TestAsset/templates/namespaced/fred',
                ],
            ],
        ]);

        $result = $renderer->render('ns::a');
        self::assertStringContainsString('<h1>Fred A</h1>', $result);
    }

    public function testWillRenderAViewModel(): void
    {
        $renderer = $this->rendererWithConfig([
            'templates' => [
                'layout' => 'layout',
                'map'    => [
                    'layout'  => __DIR__ . '/TestAsset/templates/layout/layout.phtml',
                    'content' => __DIR__ . '/TestAsset/templates/default-param.phtml',
                ],
            ],
        ]);

        $viewModel = new ViewModel(['global' => 'Laminas'], 'content');
        $result    = $renderer->render('content', $viewModel);

        self::assertStringContainsString('<content>Laminas</content>', $result);
    }

    public function testCanRenderNestedViewModels(): void
    {
        $renderer = $this->rendererWithConfig([
            'templates' => [
                'layout' => 'layout',
                'map'    => [
                    'layout'  => __DIR__ . '/TestAsset/templates/layout/layout.phtml',
                    'content' => __DIR__ . '/TestAsset/templates/nested/parent.phtml',
                    'child'   => __DIR__ . '/TestAsset/templates/nested/child.phtml',
                ],
            ],
        ]);

        $child  = new ViewModel([], 'child');
        $parent = new ViewModel([], 'content', ['child' => $child]);

        $result = $renderer->render('content', $parent);

        self::assertStringContainsString('<layout>', $result);
        self::assertStringContainsString('<parent>', $result);
        self::assertStringContainsString('<child>Foo</child>', $result);
    }

    public function testRenderChildWithDefaultParameter(): void
    {
        $renderer = $this->rendererWithConfig([
            'templates' => [
                'layout' => 'layout',
                'map'    => [
                    'layout'  => __DIR__ . '/TestAsset/templates/layout/layout.phtml',
                    'content' => __DIR__ . '/TestAsset/templates/nested/parent.phtml',
                    'child'   => __DIR__ . '/TestAsset/templates/nested/child-with-param.phtml',
                ],
            ],
        ]);

        $renderer->addDefaultParam('child', 'global', 'CHILD DEFAULT');

        $viewModel = new ViewModel([], 'content', [
            'child' => new ViewModel([], 'child'),
        ]);

        $result = $renderer->render('content', $viewModel);

        self::assertStringContainsString('<child>CHILD DEFAULT</child>', $result);
    }

    public function testCanRenderWithCustomDefaultSuffix(): void
    {
        $renderer = $this->rendererWithConfig([
            'templates' => [
                'layout'    => 'layout',
                'extension' => 'muppet',
                'map'       => [
                    'layout' => __DIR__ . '/TestAsset/templates/layout/layout.phtml',
                ],
                'paths'     => [
                    'ns' => __DIR__ . '/TestAsset/templates/suffix',
                ],
            ],
        ]);

        $result = $renderer->render('ns::kermit');

        self::assertStringContainsString('<h1>Kermit</h1>', $result);
    }

    public function testChangeLayoutInTemplateViaLayoutPlugin(): void
    {
        $renderer = $this->rendererWithConfig([
            'templates' => [
                'layout' => 'layout',
                'map'    => [
                    'layout'      => __DIR__ . '/TestAsset/templates/layout/layout.phtml',
                    'alternative' => __DIR__ . '/TestAsset/templates/layout/alternative.phtml',
                    'content'     => __DIR__ . '/TestAsset/templates/change-layout.phtml',
                ],
            ],
        ]);

        $result = $renderer->render('content');

        self::assertStringContainsString('<alt-layout>', $result);
        self::assertStringContainsString('</alt-layout>', $result);
        self::assertStringContainsString('<h1>Some Content</h1>', $result);
    }
}
