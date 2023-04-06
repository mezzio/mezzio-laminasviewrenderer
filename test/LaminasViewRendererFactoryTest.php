<?php

declare(strict_types=1);

namespace MezzioTest\LaminasView;

use Laminas\View\HelperPluginManager;
use Laminas\View\Model\ModelInterface;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\View\Resolver\AggregateResolver;
use Laminas\View\Resolver\TemplateMapResolver;
use Mezzio\Helper;
use Mezzio\LaminasView\LaminasViewRenderer;
use Mezzio\LaminasView\LaminasViewRendererFactory;
use Mezzio\LaminasView\NamespacedPathStackResolver;
use Mezzio\LaminasView\ServerUrlHelper;
use Mezzio\LaminasView\UrlHelper;
use Mezzio\Template\TemplatePath;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;

use function assert;
use function PHPUnit\Framework\never;
use function sprintf;
use function var_export;

use const DIRECTORY_SEPARATOR;

class LaminasViewRendererFactoryTest extends TestCase
{
    /** @var ContainerInterface&MockObject */
    private ContainerInterface $container;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
    }

    /**
     * @psalm-return array<array-key, string|string[]>
     */
    private function getConfigurationPaths(): array
    {
        return [
            'foo' => __DIR__ . '/TestAsset/bar',
            1     => __DIR__ . '/TestAsset/one',
            'bar' => [
                __DIR__ . '/TestAsset/baz',
                __DIR__ . '/TestAsset/bat',
            ],
            0     => [
                __DIR__ . '/TestAsset/two',
                __DIR__ . '/TestAsset/three',
            ],
        ];
    }

    private function assertPathsHasNamespace(
        ?string $namespace,
        array $paths,
        ?string $message = null
    ): void {
        $message = $message ?: sprintf('Paths do not contain namespace %s', $namespace ?: 'null');

        $found = false;
        foreach ($paths as $path) {
            $this->assertInstanceOf(TemplatePath::class, $path, 'Non-TemplatePath found in paths list');
            if ($path->getNamespace() === $namespace) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, $message);
    }

    private function assertPathNamespaceCount(
        int $expected,
        ?string $namespace,
        array $paths,
        ?string $message = null
    ): void {
        $message = $message ?: sprintf('Did not find %d paths with namespace %s', $expected, $namespace ?: 'null');

        $count = 0;
        foreach ($paths as $path) {
            $this->assertInstanceOf(TemplatePath::class, $path, 'Non-TemplatePath found in paths list');
            if ($path->getNamespace() === $namespace) {
                $count += 1;
            }
        }
        $this->assertSame($expected, $count, $message);
    }

    private function assertPathNamespaceContains(
        mixed $expected,
        ?string $namespace,
        array $paths,
        ?string $message = null
    ): void {
        $message = $message ?: sprintf('Did not find path %s in namespace %s', (string) $expected, $namespace ?: '');

        $found = [];
        foreach ($paths as $path) {
            $this->assertInstanceOf(TemplatePath::class, $path, 'Non-TemplatePath found in paths list');
            if ($path->getNamespace() === $namespace) {
                $found[] = $path->getPath();
            }
        }
        $this->assertContains($expected, $found, $message);
    }

    private function fetchPhpRenderer(LaminasViewRenderer $view): PhpRenderer
    {
        $r        = new ReflectionProperty($view, 'renderer');
        $renderer = $r->getValue($view);
        assert($renderer instanceof PhpRenderer);

        return $renderer;
    }

    public function testCallingFactoryWithNoConfigReturnsLaminasViewInstance(): LaminasViewRenderer
    {
        $this->container->expects(self::exactly(5))
            ->method('has')
            ->willReturnMap([
                ['config', false],
                [HelperPluginManager::class, false],
                ['Zend\View\HelperPluginManager', false],
                [PhpRenderer::class, false],
                ['Zend\View\Renderer\PhpRenderer', false],
            ]);

        $this->container->expects(never())
            ->method('get');

        $factory = new LaminasViewRendererFactory();
        $view    = $factory($this->container);
        $this->assertInstanceOf(LaminasViewRenderer::class, $view);
        return $view;
    }

    /**
     * @depends testCallingFactoryWithNoConfigReturnsLaminasViewInstance
     */
    public function testUnConfiguredLaminasViewInstanceContainsNoPaths(LaminasViewRenderer $view): void
    {
        $paths = $view->getPaths();
        $this->assertEmpty($paths);
    }

    public function testConfiguresLayout(): void
    {
        $config = [
            'templates' => [
                'layout' => 'layout/layout',
            ],
        ];

        $this->container->expects(self::exactly(5))
            ->method('has')
            ->willReturnMap([
                ['config', true],
                [HelperPluginManager::class, false],
                ['Zend\View\HelperPluginManager', false],
                [PhpRenderer::class, false],
                ['Zend\View\Renderer\PhpRenderer', false],
            ]);

        $this->container->expects(self::once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $factory = new LaminasViewRendererFactory();
        $view    = $factory($this->container);

        $r      = new ReflectionProperty($view, 'layout');
        $layout = $r->getValue($view);
        $this->assertInstanceOf(ModelInterface::class, $layout);
        $this->assertSame($config['templates']['layout'], $layout->getTemplate());
    }

    public function testConfiguresPaths(): void
    {
        $config = [
            'templates' => [
                'paths' => $this->getConfigurationPaths(),
            ],
        ];

        $this->container->expects(self::exactly(5))
            ->method('has')
            ->willReturnMap([
                ['config', true],
                [HelperPluginManager::class, false],
                ['Zend\View\HelperPluginManager', false],
                [PhpRenderer::class, false],
                ['Zend\View\Renderer\PhpRenderer', false],
            ]);

        $this->container->expects(self::once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $factory = new LaminasViewRendererFactory();
        $view    = $factory($this->container);

        $paths = $view->getPaths();
        $this->assertPathsHasNamespace('foo', $paths);
        $this->assertPathsHasNamespace('bar', $paths);
        $this->assertPathsHasNamespace(null, $paths);

        $this->assertPathNamespaceCount(1, 'foo', $paths);
        $this->assertPathNamespaceCount(2, 'bar', $paths);
        $this->assertPathNamespaceCount(3, null, $paths);

        $dirSlash = DIRECTORY_SEPARATOR;

        $this->assertPathNamespaceContains(
            __DIR__ . '/TestAsset/bar' . $dirSlash,
            'foo',
            $paths,
            var_export($paths, true)
        );
        $this->assertPathNamespaceContains(__DIR__ . '/TestAsset/baz' . $dirSlash, 'bar', $paths);
        $this->assertPathNamespaceContains(__DIR__ . '/TestAsset/bat' . $dirSlash, 'bar', $paths);
        $this->assertPathNamespaceContains(__DIR__ . '/TestAsset/one' . $dirSlash, null, $paths);
        $this->assertPathNamespaceContains(__DIR__ . '/TestAsset/two' . $dirSlash, null, $paths);
        $this->assertPathNamespaceContains(__DIR__ . '/TestAsset/three' . $dirSlash, null, $paths);
    }

    public function testConfiguresTemplateMap(): void
    {
        $config = [
            'templates' => [
                'map' => [
                    'foo' => 'bar',
                    'bar' => 'baz',
                ],
            ],
        ];

        $this->container->expects(self::exactly(5))
            ->method('has')
            ->willReturnMap([
                ['config', true],
                [HelperPluginManager::class, false],
                ['Zend\View\HelperPluginManager', false],
                [PhpRenderer::class, false],
                ['Zend\View\Renderer\PhpRenderer', false],
            ]);

        $this->container->expects(self::once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $factory = new LaminasViewRendererFactory();
        $view    = $factory($this->container);

        $r        = new ReflectionProperty($view, 'renderer');
        $renderer = $r->getValue($view);
        assert($renderer instanceof PhpRenderer);
        $aggregate = $renderer->resolver();
        $this->assertInstanceOf(AggregateResolver::class, $aggregate);
        $resolver = false;
        foreach ($aggregate as $resolver) {
            if ($resolver instanceof TemplateMapResolver) {
                break;
            }
        }
        $this->assertInstanceOf(TemplateMapResolver::class, $resolver, 'Expected TemplateMapResolver not found!');
        $this->assertTrue($resolver->has('foo'));
        $this->assertEquals('bar', $resolver->get('foo'));
        $this->assertTrue($resolver->has('bar'));
        $this->assertEquals('baz', $resolver->get('bar'));
    }

    public function testConfiguresCustomDefaultSuffix(): void
    {
        $config = [
            'templates' => [
                'extension' => 'php',
            ],
        ];

        $this->container->expects(self::exactly(5))
            ->method('has')
            ->willReturnMap([
                ['config', true],
                [HelperPluginManager::class, false],
                ['Zend\View\HelperPluginManager', false],
                [PhpRenderer::class, false],
                ['Zend\View\Renderer\PhpRenderer', false],
            ]);

        $this->container->expects(self::once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $factory = new LaminasViewRendererFactory();
        $view    = $factory($this->container);

        $r        = new ReflectionProperty($view, 'resolver');
        $resolver = $r->getValue($view);

        $this->assertInstanceOf(
            NamespacedPathStackResolver::class,
            $resolver,
            'Expected NamespacedPathStackResolver not found!'
        );
        $this->assertEquals('php', $resolver->getDefaultSuffix());
    }

    public function testConfiguresDeprecatedDefaultSuffix(): void
    {
        $config = [
            'templates' => [
                'default_suffix' => 'php',
            ],
        ];

        $this->container->expects(self::exactly(5))
            ->method('has')
            ->willReturnMap([
                ['config', true],
                [HelperPluginManager::class, false],
                ['Zend\View\HelperPluginManager', false],
                [PhpRenderer::class, false],
                ['Zend\View\Renderer\PhpRenderer', false],
            ]);

        $this->container->expects(self::once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $factory = new LaminasViewRendererFactory();
        $view    = $factory($this->container);

        $r        = new ReflectionProperty($view, 'resolver');
        $resolver = $r->getValue($view);

        $this->assertInstanceOf(
            NamespacedPathStackResolver::class,
            $resolver,
            'Expected NamespacedPathStackResolver not found!'
        );
        $this->assertEquals('php', $resolver->getDefaultSuffix());
    }

    public function testInjectsCustomHelpersIntoHelperManager(): void
    {
        $this->container->expects(self::atLeast(5))
            ->method('has')
            ->willReturnMap([
                ['config', false],
                [HelperPluginManager::class, false],
                ['Zend\View\HelperPluginManager', false],
                [PhpRenderer::class, false],
                ['Zend\View\Renderer\PhpRenderer', false],
                [Helper\UrlHelper::class, true],
                [Helper\ServerUrlHelper::class, true],
            ]);

        $this->container->expects(self::atLeast(2))
            ->method('get')
            ->willReturnMap([
                [Helper\UrlHelper::class, $this->createMock(Helper\UrlHelper::class)],
                [Helper\ServerUrlHelper::class, $this->createMock(Helper\ServerUrlHelper::class)],
            ]);

        $factory = new LaminasViewRendererFactory();
        $view    = $factory($this->container);
        $this->assertInstanceOf(LaminasViewRenderer::class, $view);

        $renderer = $this->fetchPhpRenderer($view);
        $helpers  = $renderer->getHelperPluginManager();
        $this->assertInstanceOf(HelperPluginManager::class, $helpers);
        $this->assertTrue($helpers->has('url'));
        $this->assertTrue($helpers->has('serverurl'));
        $this->assertInstanceOf(UrlHelper::class, $helpers->get('url'));
        $this->assertInstanceOf(ServerUrlHelper::class, $helpers->get('serverurl'));
    }

    public function testWillUseHelperManagerFromContainer(): void
    {
        $this->container->expects(self::exactly(4))
            ->method('has')
            ->willReturnMap([
                ['config', false],
                [HelperPluginManager::class, true],
                [PhpRenderer::class, false],
                ['Zend\View\Renderer\PhpRenderer', false],
            ]);

        $helpers = new HelperPluginManager($this->container);

        $this->container->expects(self::exactly(1))
            ->method('get')
            ->willReturnMap([
                [HelperPluginManager::class, $helpers],
            ]);

        $factory = new LaminasViewRendererFactory();
        $view    = $factory($this->container);
        $this->assertInstanceOf(LaminasViewRenderer::class, $view);

        $renderer = $this->fetchPhpRenderer($view);
        $this->assertSame($helpers, $renderer->getHelperPluginManager());
    }

    public function testUrlAndServerUrlHelpersAreRegisteredWithTheExpectedAliases(): void
    {
        $this->container->expects(self::atLeast(6))
            ->method('has')
            ->willReturnMap([
                ['config', false],
                [HelperPluginManager::class, true],
                ['Zend\View\HelperPluginManager', false],
                [PhpRenderer::class, false],
                ['Zend\View\Renderer\PhpRenderer', false],
                [Helper\UrlHelper::class, true],
                [Helper\ServerUrlHelper::class, true],
            ]);

        $helpers   = new HelperPluginManager($this->container);
        $urlHelper = $this->createMock(Helper\UrlHelper::class);
        $serverUrl = $this->createMock(Helper\ServerUrlHelper::class);

        $this->container->expects(self::exactly(3))
            ->method('get')
            ->willReturnMap([
                [HelperPluginManager::class, $helpers],
                [Helper\UrlHelper::class, $urlHelper],
                [Helper\ServerUrlHelper::class, $serverUrl],
            ]);

        $factory = new LaminasViewRendererFactory();
        $factory($this->container);

        $this->assertTrue($helpers->has('url'));
        $this->assertInstanceOf(UrlHelper::class, $helpers->get('url'));

        $this->assertTrue($helpers->has('Url'));
        $this->assertInstanceOf(UrlHelper::class, $helpers->get('Url'));

        $this->assertTrue($helpers->has('serverurl'));
        $this->assertInstanceOf(ServerUrlHelper::class, $helpers->get('serverurl'));

        $this->assertTrue($helpers->has('ServerUrl'));
        $this->assertInstanceOf(ServerUrlHelper::class, $helpers->get('ServerUrl'));

        $this->assertTrue($helpers->has('serverUrl'));
        $this->assertInstanceOf(ServerUrlHelper::class, $helpers->get('serverUrl'));
    }

    public function testWillUseRendererFromContainer(): void
    {
        $this->container->expects(self::exactly(4))
            ->method('has')
            ->willReturnMap([
                ['config', false],
                [HelperPluginManager::class, false],
                ['Zend\View\HelperPluginManager', false],
                [PhpRenderer::class, true],
            ]);

        $engine = new PhpRenderer();

        $this->container->expects(self::atLeast(1))
            ->method('get')
            ->willReturnMap([
                [PhpRenderer::class, $engine],
            ]);

        $factory = new LaminasViewRendererFactory();
        $view    = $factory($this->container);

        $composed = $this->fetchPhpRenderer($view);
        $this->assertSame($engine, $composed);
    }
}
