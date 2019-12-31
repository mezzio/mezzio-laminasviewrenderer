<?php

/**
 * @see       https://github.com/mezzio/mezzio-laminasviewrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-laminasviewrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-laminasviewrenderer/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\LaminasView;

use Interop\Container\ContainerInterface;
use Laminas\View\HelperPluginManager;
use Laminas\View\Model\ModelInterface;
use Laminas\View\Resolver\AggregateResolver;
use Laminas\View\Resolver\TemplateMapResolver;
use Mezzio\Helper;
use Mezzio\LaminasView\LaminasViewRenderer;
use Mezzio\LaminasView\LaminasViewRendererFactory;
use Mezzio\LaminasView\ServerUrlHelper;
use Mezzio\LaminasView\UrlHelper;
use Mezzio\Router\RouterInterface;
use Mezzio\Template\TemplatePath;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionProperty;

class LaminasViewRendererFactoryTest extends TestCase
{
    /**
     * @var ContainerInterface
    */
    private $container;

    public function getConfigurationPaths()
    {
        return [
            'foo' => __DIR__ . '/TestAsset/bar',
            1 => __DIR__ . '/TestAsset/one',
            'bar' => [
                __DIR__ . '/TestAsset/baz',
                __DIR__ . '/TestAsset/bat',
            ],
            0 => [
                __DIR__ . '/TestAsset/two',
                __DIR__ . '/TestAsset/three',
            ],
        ];
    }

    public function assertPathsHasNamespace($namespace, array $paths, $message = null)
    {
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

    public function assertPathNamespaceCount($expected, $namespace, array $paths, $message = null)
    {
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

    public function assertPathNamespaceContains($expected, $namespace, array $paths, $message = null)
    {
        $message = $message ?: sprintf('Did not find path %s in namespace %s', $expected, $namespace ?: null);

        $found = [];
        foreach ($paths as $path) {
            $this->assertInstanceOf(TemplatePath::class, $path, 'Non-TemplatePath found in paths list');
            if ($path->getNamespace() === $namespace) {
                $found[] = $path->getPath();
            }
        }
        $this->assertContains($expected, $found, $message);
    }

    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    public function fetchPhpRenderer(LaminasViewRenderer $view)
    {
        $r = new ReflectionProperty($view, 'renderer');
        $r->setAccessible(true);
        return $r->getValue($view);
    }

    public function injectContainerService($name, $service)
    {
        $this->container->has($name)->willReturn(true);
        $this->container->get($name)->willReturn(
            $service instanceof ObjectProphecy ? $service->reveal() : $service
        );
    }

    public function injectBaseHelpers()
    {
        $this->injectContainerService(
            Helper\UrlHelper::class,
            $this->prophesize(Helper\UrlHelper::class)
        );
        $this->injectContainerService(
            Helper\ServerUrlHelper::class,
            $this->prophesize(Helper\ServerUrlHelper::class)
        );
    }

    public function testCallingFactoryWithNoConfigReturnsLaminasViewInstance()
    {
        $this->container->has('config')->willReturn(false);
        $this->container->has(HelperPluginManager::class)->willReturn(false);
        $this->container->has(\Zend\View\HelperPluginManager::class)->willReturn(false);
        $this->injectBaseHelpers();
        $factory = new LaminasViewRendererFactory();
        $view    = $factory($this->container->reveal());
        $this->assertInstanceOf(LaminasViewRenderer::class, $view);
        return $view;
    }

    /**
     * @depends testCallingFactoryWithNoConfigReturnsLaminasViewInstance
     */
    public function testUnconfiguredLaminasViewInstanceContainsNoPaths(LaminasViewRenderer $view)
    {
        $paths = $view->getPaths();
        $this->assertInternalType('array', $paths);
        $this->assertEmpty($paths);
    }

    public function testConfiguresLayout()
    {
        $config = [
            'templates' => [
                'layout' => 'layout/layout',
            ],
        ];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->container->has(HelperPluginManager::class)->willReturn(false);
        $this->container->has(\Zend\View\HelperPluginManager::class)->willReturn(false);
        $this->injectBaseHelpers();
        $factory = new LaminasViewRendererFactory();
        $view = $factory($this->container->reveal());

        $r = new ReflectionProperty($view, 'layout');
        $r->setAccessible(true);
        $layout = $r->getValue($view);
        $this->assertInstanceOf(ModelInterface::class, $layout);
        $this->assertSame($config['templates']['layout'], $layout->getTemplate());
    }

    public function testConfiguresPaths()
    {
        $config = [
            'templates' => [
                'paths' => $this->getConfigurationPaths(),
            ],
        ];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->container->has(HelperPluginManager::class)->willReturn(false);
        $this->container->has(\Zend\View\HelperPluginManager::class)->willReturn(false);
        $this->injectBaseHelpers();
        $factory = new LaminasViewRendererFactory();
        $view = $factory($this->container->reveal());

        $paths = $view->getPaths();
        $this->assertPathsHasNamespace('foo', $paths);
        $this->assertPathsHasNamespace('bar', $paths);
        $this->assertPathsHasNamespace(null, $paths);

        $this->assertPathNamespaceCount(1, 'foo', $paths);
        $this->assertPathNamespaceCount(2, 'bar', $paths);
        $this->assertPathNamespaceCount(3, null, $paths);

        $dirSlash = DIRECTORY_SEPARATOR;
        // @codingStandardsIgnoreStart
        $this->assertPathNamespaceContains(__DIR__ . '/TestAsset/bar' . $dirSlash, 'foo', $paths, var_export($paths, 1));
        $this->assertPathNamespaceContains(__DIR__ . '/TestAsset/baz' . $dirSlash, 'bar', $paths);
        $this->assertPathNamespaceContains(__DIR__ . '/TestAsset/bat' . $dirSlash, 'bar', $paths);
        $this->assertPathNamespaceContains(__DIR__ . '/TestAsset/one' . $dirSlash, null, $paths);
        $this->assertPathNamespaceContains(__DIR__ . '/TestAsset/two' . $dirSlash, null, $paths);
        $this->assertPathNamespaceContains(__DIR__ . '/TestAsset/three' . $dirSlash, null, $paths);
        // @codingStandardsIgnoreEnd
    }

    public function testConfiguresTemplateMap()
    {
        $config = [
            'templates' => [
                'map' => [
                    'foo' => 'bar',
                    'bar' => 'baz',
                ],
            ],
        ];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->container->has(HelperPluginManager::class)->willReturn(false);
        $this->container->has(\Zend\View\HelperPluginManager::class)->willReturn(false);
        $this->injectBaseHelpers();
        $factory = new LaminasViewRendererFactory();
        $view = $factory($this->container->reveal());

        $r = new ReflectionProperty($view, 'renderer');
        $r->setAccessible(true);
        $renderer  = $r->getValue($view);
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

    public function testInjectsCustomHelpersIntoHelperManager()
    {
        $router = $this->prophesize(RouterInterface::class)->reveal();
        $this->container->has('config')->willReturn(false);
        $this->container->has(HelperPluginManager::class)->willReturn(false);
        $this->container->has(\Zend\View\HelperPluginManager::class)->willReturn(false);
        $this->injectBaseHelpers();
        $factory = new LaminasViewRendererFactory();
        $view    = $factory($this->container->reveal());
        $this->assertInstanceOf(LaminasViewRenderer::class, $view);

        $renderer = $this->fetchPhpRenderer($view);
        $helpers  = $renderer->getHelperPluginManager();
        $this->assertInstanceOf(HelperPluginManager::class, $helpers);
        $this->assertTrue($helpers->has('url'));
        $this->assertTrue($helpers->has('serverurl'));
        $this->assertInstanceOf(UrlHelper::class, $helpers->get('url'));
        $this->assertInstanceOf(ServerUrlHelper::class, $helpers->get('serverurl'));
    }

    public function testWillUseHelperManagerFromContainer()
    {
        $router = $this->prophesize(RouterInterface::class)->reveal();
        $this->container->has('config')->willReturn(false);
        $this->injectBaseHelpers();

        $helpers = new HelperPluginManager();
        $this->container->has(HelperPluginManager::class)->willReturn(true);
        $this->container->get(HelperPluginManager::class)->willReturn($helpers);
        $factory = new LaminasViewRendererFactory();
        $view    = $factory($this->container->reveal());
        $this->assertInstanceOf(LaminasViewRenderer::class, $view);

        $renderer = $this->fetchPhpRenderer($view);
        $this->assertSame($helpers, $renderer->getHelperPluginManager());
        return $helpers;
    }

    /**
     * @depends testWillUseHelperManagerFromContainer
     */
    public function testInjectsCustomHelpersIntoHelperManagerFromContainer(HelperPluginManager $helpers)
    {
        $this->assertTrue($helpers->has('url'));
        $this->assertTrue($helpers->has('serverurl'));
        $this->assertInstanceOf(UrlHelper::class, $helpers->get('url'));
        $this->assertInstanceOf(ServerUrlHelper::class, $helpers->get('serverurl'));
    }
}
