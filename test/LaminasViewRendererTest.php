<?php

declare(strict_types=1);

namespace MezzioTest\LaminasView;

use ArrayObject;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\View\Resolver\TemplatePathStack;
use Mezzio\LaminasView\LaminasViewRenderer;
use Mezzio\Template\Exception\InvalidArgumentException;
use Mezzio\Template\TemplatePath;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

use function assert;
use function file_get_contents;
use function sprintf;
use function str_replace;
use function trim;
use function uniqid;
use function var_export;

use const DIRECTORY_SEPARATOR;
use const PHP_EOL;

class LaminasViewRendererTest extends TestCase
{
    private PhpRenderer $render;

    protected function setUp(): void
    {
        $resolver     = new TemplatePathStack();
        $this->render = new PhpRenderer();
        $this->render->setResolver($resolver);
    }

    public function assertTemplatePath(string $path, TemplatePath $templatePath, ?string $message = null): void
    {
        $message = $message ?: sprintf('Failed to assert TemplatePath contained path %s', $path);
        $this->assertEquals($path, $templatePath->getPath(), $message);
    }

    public function assertTemplatePathString(string $path, TemplatePath $templatePath, ?string $message = null): void
    {
        $message = $message ?: sprintf('Failed to assert TemplatePath casts to string path %s', $path);
        $this->assertEquals($path, (string) $templatePath, $message);
    }

    public function assertTemplatePathNamespace(
        string $namespace,
        TemplatePath $templatePath,
        ?string $message = null
    ): void {
        $message = $message
            ?: sprintf('Failed to assert TemplatePath namespace matched %s', var_export($namespace, true));
        $this->assertEquals($namespace, $templatePath->getNamespace(), $message);
    }

    public function assertEmptyTemplatePathNamespace(TemplatePath $templatePath, ?string $message = null): void
    {
        $message = $message ?: 'Failed to assert TemplatePath namespace was empty';
        $this->assertEmpty($templatePath->getNamespace(), $message);
    }

    public function assertEqualTemplatePath(
        TemplatePath $expected,
        TemplatePath $received,
        ?string $message = null
    ): void {
        $message = $message ?: 'Failed to assert TemplatePaths are equal';
        if (
            $expected->getPath() !== $received->getPath()
            || $expected->getNamespace() !== $received->getNamespace()
        ) {
            $this->fail($message);
        }
    }

    private function retrieveRenderer(LaminasViewRenderer $laminasViewRenderer): PhpRenderer
    {
        $property = new ReflectionProperty(LaminasViewRenderer::class, 'renderer');
        $property->setAccessible(true);

        $renderer = $property->getValue($laminasViewRenderer);
        assert($renderer instanceof PhpRenderer);

        return $renderer;
    }

    public function testCanPassRendererToConstructor(): void
    {
        $renderer = new LaminasViewRenderer($this->render);
        $this->assertInstanceOf(LaminasViewRenderer::class, $renderer);
        $this->assertSame($this->render, $this->retrieveRenderer($renderer));
    }

    public function testInstantiatingWithoutEngineLazyLoadsOne(): void
    {
        $renderer = new LaminasViewRenderer();
        $this->assertInstanceOf(LaminasViewRenderer::class, $renderer);
        $this->assertInstanceOf(PhpRenderer::class, $this->retrieveRenderer($renderer));
    }

    public function testInstantiatingWithInvalidLayout(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Layout must be a string layout template name');

        /** @psalm-suppress InvalidArgument */
        new LaminasViewRenderer(null, []);
    }

    public function testCanAddPathWithEmptyNamespace(): void
    {
        $renderer = new LaminasViewRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $paths = $renderer->getPaths();
        $this->assertCount(1, $paths);
        $this->assertTemplatePath(__DIR__ . '/TestAsset' . DIRECTORY_SEPARATOR, $paths[0]);
        $this->assertTemplatePathString(__DIR__ . '/TestAsset' . DIRECTORY_SEPARATOR, $paths[0]);
        $this->assertEmptyTemplatePathNamespace($paths[0]);
    }

    public function testCanAddPathWithNamespace(): void
    {
        $renderer = new LaminasViewRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset', 'test');
        $paths = $renderer->getPaths();
        $this->assertCount(1, $paths);
        $this->assertTemplatePath(__DIR__ . '/TestAsset' . DIRECTORY_SEPARATOR, $paths[0]);
        $this->assertTemplatePathString(__DIR__ . '/TestAsset' . DIRECTORY_SEPARATOR, $paths[0]);
        $this->assertTemplatePathNamespace('test', $paths[0]);
    }

    public function testDelegatesRenderingToUnderlyingImplementation(): void
    {
        $renderer = new LaminasViewRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name   = 'laminasview';
        $result = $renderer->render('laminasview', ['name' => $name]);
        $this->assertStringContainsString($name, $result);
        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $content = str_replace('<?php echo $name ?>', $name, $content);
        $this->assertEquals($content, $result);
    }

    /** @return array<array-key, array<array-key, mixed>> */
    public function invalidParameterValues(): array
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

    /**
     * @dataProvider invalidParameterValues
     */
    public function testRenderRaisesExceptionForInvalidParameterTypes(mixed $params): void
    {
        $renderer = new LaminasViewRenderer();
        $this->expectException(InvalidArgumentException::class);

        /** @psalm-suppress MixedArgument */
        $renderer->render('foo', $params);
    }

    public function testCanRenderWithNullParams(): void
    {
        $renderer = new LaminasViewRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $result  = $renderer->render('laminasview-null', null);
        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview-null.phtml');
        $this->assertEquals($content, $result);
    }

    /** @return array<string, array{0: object, 1: string}> */
    public function objectParameterValues(): array
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

    /**
     * @dataProvider objectParameterValues
     */
    public function testCanRenderWithParameterObjects(object $params, string $search): void
    {
        $renderer = new LaminasViewRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $result = $renderer->render('laminasview', $params);
        $this->assertStringContainsString($search, $result);
        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $content = str_replace('<?php echo $name ?>', $search, $content);
        $this->assertEquals($content, $result);
    }

    /**
     * @group layout
     */
    public function testWillRenderContentInLayoutPassedToConstructor(): void
    {
        $renderer = new LaminasViewRenderer(null, 'laminasview-layout');
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name   = 'laminasview';
        $result = $renderer->render('laminasview', ['name' => $name]);
        $this->assertStringContainsString($name, $result);
        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $content = str_replace('<?php echo $name ?>', $name, $content);
        $this->assertStringContainsString($content, $result);
        $this->assertStringContainsString('<title>Layout Page</title>', $result, sprintf('Received %s', $result));
    }

    public function testSharedParameterIsAvailableInLayout(): void
    {
        $renderer = new LaminasViewRenderer(null, 'laminasview-layout-variable');
        $renderer->addPath(__DIR__ . '/TestAsset');
        $title = uniqid('LaminasViewTitle', true);
        $renderer->addDefaultParam($renderer::TEMPLATE_ALL, 'title', $title);

        $name   = uniqid('LaminasViewName', true);
        $result = $renderer->render('laminasview', ['name' => $name]);

        $this->assertStringContainsString($title, $result);
        $this->assertStringContainsString($name, $result);
        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $content = str_replace('<?php echo $name ?>', $name, $content);
        $this->assertStringContainsString($content, $result);
        $expected = sprintf('<title>Layout Page: %s</title>', $title);
        $this->assertStringContainsString($expected, $result, sprintf('Received %s', $result));
    }

    public function testTemplateDefaultParameterIsNotAvailableInLayout(): void
    {
        $renderer = new LaminasViewRenderer(null, 'laminasview-layout-variable');
        $renderer->addPath(__DIR__ . '/TestAsset');
        $title = uniqid('LaminasViewTitle', true);
        $renderer->addDefaultParam('laminasview', 'title', $title);

        $name   = uniqid('LaminasViewName', true);
        $result = $renderer->render('laminasview', ['name' => $name]);

        $this->assertStringNotContainsString($title, $result);
        $this->assertStringContainsString($name, $result);
        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $content = str_replace('<?php echo $name ?>', $name, $content);
        $this->assertStringContainsString($content, $result);
        $expected = sprintf('<title>Layout Page: %s</title>', '');
        $this->assertStringContainsString($expected, $result, sprintf('Received %s', $result));
    }

    public function testLayoutTemplateDefaultParameterIsAvailableInLayout(): void
    {
        $renderer = new LaminasViewRenderer(null, 'laminasview-layout-variable');
        $renderer->addPath(__DIR__ . '/TestAsset');
        $title = uniqid('LaminasViewTitle', true);
        $name  = uniqid('LaminasViewName', true);
        $renderer->addDefaultParam('laminasview-layout-variable', 'title', $title);
        $result = $renderer->render('laminasview', ['name' => $name]);
        $this->assertStringContainsString($title, $result);
        $this->assertStringContainsString($name, $result);

        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $content = str_replace('<?php echo $name ?>', $name, $content);
        $layout  = file_get_contents(__DIR__ . '/TestAsset/laminasview-layout-variable.phtml');
        $layout  = str_replace('<?= $this->title ?>', $title, $layout);
        $layout  = str_replace('<?= $this->content ?>' . PHP_EOL, $content, $layout);
        $this->assertStringContainsString($layout, $result);

        $expected = sprintf('<title>Layout Page: %s</title>', $title);
        $this->assertStringContainsString($expected, $result, sprintf('Received %s', $result));
    }

    public function testVariableInProvidedLayoutViewModelOverridesTemplateDefaultParameter(): void
    {
        $renderer = new LaminasViewRenderer(null);
        $renderer->addPath(__DIR__ . '/TestAsset');
        $titleToBeOverriden = uniqid('LaminasViewTitleToBeOverriden', true);
        $title              = uniqid('LaminasViewTitle', true);
        $name               = uniqid('LaminasViewName', true);
        $renderer->addDefaultParam('laminasview-layout-variable', 'title', $titleToBeOverriden);

        $layout = new ViewModel(['title' => $title]);
        $layout->setTemplate('laminasview-layout-variable');
        $result = $renderer->render('laminasview', ['name' => $name, 'layout' => $layout]);
        $this->assertStringContainsString($title, $result);
        $this->assertStringContainsString($name, $result);

        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $content = str_replace('<?php echo $name ?>', $name, $content);
        $layout  = file_get_contents(__DIR__ . '/TestAsset/laminasview-layout-variable.phtml');
        $layout  = str_replace('<?= $this->title ?>', $title, $layout);
        $layout  = str_replace('<?= $this->content ?>' . PHP_EOL, $content, $layout);
        $this->assertStringContainsString($layout, $result);

        $expected = sprintf('<title>Layout Page: %s</title>', $title);
        $this->assertStringContainsString($expected, $result, sprintf('Received %s', $result));
    }

    public function testTemplateDefaultParameterIsAvailableInLayoutProvidedWithViewModel(): void
    {
        $renderer = new LaminasViewRenderer(null);
        $renderer->addPath(__DIR__ . '/TestAsset');
        $title = uniqid('LaminasViewTitle', true);
        $name  = uniqid('LaminasViewName', true);
        $renderer->addDefaultParam('laminasview-layout-variable', 'title', $title);

        $layout = new ViewModel();
        $layout->setTemplate('laminasview-layout-variable');
        $result = $renderer->render('laminasview', ['name' => $name, 'layout' => $layout]);
        $this->assertStringContainsString($title, $result);
        $this->assertStringContainsString($name, $result);

        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $content = str_replace('<?php echo $name ?>', $name, $content);
        $layout  = file_get_contents(__DIR__ . '/TestAsset/laminasview-layout-variable.phtml');
        $layout  = str_replace('<?= $this->title ?>', $title, $layout);
        $layout  = str_replace('<?= $this->content ?>' . PHP_EOL, $content, $layout);
        $this->assertStringContainsString($layout, $result);

        $expected = sprintf('<title>Layout Page: %s</title>', $title);
        $this->assertStringContainsString($expected, $result, sprintf('Received %s', $result));
    }

    /**
     * @group layout
     */
    public function testWillRenderContentInLayoutPassedDuringRendering(): void
    {
        $renderer = new LaminasViewRenderer(null);
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name   = 'laminasview';
        $result = $renderer->render('laminasview', ['name' => $name, 'layout' => 'laminasview-layout']);
        $this->assertStringContainsString($name, $result);
        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $content = str_replace('<?php echo $name ?>', $name, $content);
        $this->assertStringContainsString($content, $result);

        $this->assertStringContainsString('<title>Layout Page</title>', $result);
    }

    /**
     * @group layout
     */
    public function testLayoutPassedWhenRenderingOverridesLayoutPassedToConstructor(): void
    {
        $renderer = new LaminasViewRenderer(null, 'laminasview-layout');
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name   = 'laminasview';
        $result = $renderer->render('laminasview', ['name' => $name, 'layout' => 'laminasview-layout2']);
        $this->assertStringContainsString($name, $result);
        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $content = str_replace('<?php echo $name ?>', $name, $content);
        $this->assertStringContainsString($content, $result);

        $this->assertStringContainsString('<title>ALTERNATE LAYOUT PAGE</title>', $result);
    }

    /**
     * @group layout
     */
    public function testCanPassViewModelForLayoutToConstructor(): void
    {
        $layout = new ViewModel();
        $layout->setTemplate('laminasview-layout');

        $renderer = new LaminasViewRenderer(null, $layout);
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name   = 'laminasview';
        $result = $renderer->render('laminasview', ['name' => $name]);
        $this->assertStringContainsString($name, $result);
        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $content = str_replace('<?php echo $name ?>', $name, $content);
        $this->assertStringContainsString($content, $result);
        $this->assertStringContainsString('<title>Layout Page</title>', $result, sprintf('Received %s', $result));
    }

    /**
     * @group layout
     */
    public function testCanPassViewModelForLayoutParameterWhenRendering(): void
    {
        $layout = new ViewModel();
        $layout->setTemplate('laminasview-layout2');

        $renderer = new LaminasViewRenderer(null, 'laminasview-layout');
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name   = 'laminasview';
        $result = $renderer->render('laminasview', ['name' => $name, 'layout' => $layout]);
        $this->assertStringContainsString($name, $result);
        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $content = str_replace('<?php echo $name ?>', $name, $content);
        $this->assertStringContainsString($content, $result);
        $this->assertStringContainsString('<title>ALTERNATE LAYOUT PAGE</title>', $result);
    }

    /**
     * @group layout
     */
    public function testDisableLayoutOnRender(): void
    {
        $layout = new ViewModel();
        $layout->setTemplate('laminasview-layout');

        $renderer = new LaminasViewRenderer(null, $layout);
        $renderer->addPath(__DIR__ . '/TestAsset');

        $name     = 'laminasview';
        $rendered = $renderer->render('laminasview', [
            'layout' => false,
            'name'   => $name,
        ]);

        $expected = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $expected = str_replace('<?php echo $name ?>', $name, $expected);

        $this->assertEquals($rendered, $expected);
    }

    /**
     * @group layout
     */
    public function testDisableLayoutViaDefaultParameter(): void
    {
        $layout = new ViewModel();
        $layout->setTemplate('laminasview-layout');

        $renderer = new LaminasViewRenderer(null, $layout);
        $renderer->addPath(__DIR__ . '/TestAsset');
        $renderer->addDefaultParam(TemplateRendererInterface::TEMPLATE_ALL, 'layout', false);

        $name     = 'laminasview';
        $rendered = $renderer->render('laminasview', ['name' => $name]);

        $expected = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $expected = str_replace('<?php echo $name ?>', $name, $expected);

        $this->assertEquals($rendered, $expected);
    }

    /**
     * @group namespacing
     */
    public function testProperlyResolvesNamespacedTemplate(): void
    {
        $renderer = new LaminasViewRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset/test', 'test');

        $expected = file_get_contents(__DIR__ . '/TestAsset/test/test.phtml');
        $test     = $renderer->render('test::test');

        $this->assertSame($expected, $test);
    }

    public function testAddParameterToOneTemplate(): void
    {
        $renderer = new LaminasViewRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name = 'LaminasView';
        $renderer->addDefaultParam('laminasview', 'name', $name);
        $result = $renderer->render('laminasview');

        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $content = str_replace('<?php echo $name ?>', $name, $content);
        $this->assertEquals($content, $result);
    }

    public function testAddSharedParameters(): void
    {
        $renderer = new LaminasViewRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name = 'LaminasView';
        $renderer->addDefaultParam($renderer::TEMPLATE_ALL, 'name', $name);
        $result  = $renderer->render('laminasview');
        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $content = str_replace('<?php echo $name ?>', $name, $content);
        $this->assertEquals($content, $result);

        $result  = $renderer->render('laminasview-2');
        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview-2.phtml');
        $content = str_replace('<?php echo $name ?>', $name, $content);
        $this->assertEquals($content, $result);
    }

    public function testOverrideSharedParametersPerTemplate(): void
    {
        $renderer = new LaminasViewRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name  = 'Laminas';
        $name2 = 'View';
        $renderer->addDefaultParam($renderer::TEMPLATE_ALL, 'name', $name);
        $renderer->addDefaultParam('laminasview-2', 'name', $name2);
        $result  = $renderer->render('laminasview');
        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $content = str_replace('<?php echo $name ?>', $name, $content);
        $this->assertEquals($content, $result);

        $result  = $renderer->render('laminasview-2');
        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview-2.phtml');
        $content = str_replace('<?php echo $name ?>', $name2, $content);
        $this->assertEquals($content, $result);
    }

    /**
     * @psalm-return array<string, bool[]>
     */
    public function useArrayOrViewModel(): array
    {
        return [
            'array'      => [false],
            'view-model' => [true],
        ];
    }

    /**
     * @dataProvider useArrayOrViewModel
     */
    public function testOverrideSharedParametersAtRender(bool $viewAsModel): void
    {
        $renderer = new LaminasViewRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name  = 'Laminas';
        $name2 = 'View';
        $renderer->addDefaultParam($renderer::TEMPLATE_ALL, 'name', $name);

        $viewModel = ['name' => $name2];
        $viewModel = $viewAsModel ? new ViewModel($viewModel) : $viewModel;

        $result  = $renderer->render('laminasview', $viewModel);
        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $content = str_replace('<?php echo $name ?>', $name2, $content);
        $this->assertEquals($content, $result);
    }

    public function testWillRenderAViewModel(): void
    {
        $renderer = new LaminasViewRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');

        $viewModel = new ViewModel(['name' => 'Laminas']);
        $result    = $renderer->render('laminasview', $viewModel);

        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $content = str_replace('<?php echo $name ?>', 'Laminas', $content);
        $this->assertEquals($content, $result);
    }

    public function testCanRenderWithChildViewModel(): void
    {
        $path     = __DIR__ . '/TestAsset';
        $renderer = new LaminasViewRenderer();
        $renderer->addPath($path);

        $viewModelChild = new ViewModel();
        $viewModelChild->setTemplate('laminasview-null');

        $viewModelParent = new ViewModel();
        $viewModelParent->setVariables([
            'layout' => 'laminasview-layout',
        ]);
        $viewModelParent->addChild($viewModelChild, 'name');

        $result = $renderer->render('laminasview', $viewModelParent);

        $content             = file_get_contents(sprintf('%s/laminasview-null.phtml', $path));
        $contentParent       = file_get_contents(sprintf('%s/laminasview.phtml', $path));
        $contentParentLayout = file_get_contents(sprintf('%s/laminasview-layout.phtml', $path));

        // trim is used here, because rendering engine is trimming content too
        $content = trim(str_replace('<?php echo $name ?>', $content, $contentParent));
        $content = str_replace('<?= $this->content ?>', $content, $contentParentLayout);

        $this->assertEquals($content, $result);
    }

    public function testRenderChildWithDefaultParameter(): void
    {
        $name2 = 'Foo';

        $renderer = new LaminasViewRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $renderer->addDefaultParam('laminasview-2', 'name', $name2);

        $viewModelChild = new ViewModel();
        $viewModelChild->setTemplate('laminasview-2');

        $viewModelParent = new ViewModel();
        $viewModelParent->addChild($viewModelChild, 'name');

        $result = $renderer->render('laminasview', $viewModelParent);

        $contentChild = file_get_contents(__DIR__ . '/TestAsset/laminasview-2.phtml');
        $contentChild = str_replace('<?php echo $name ?>', $name2, $contentChild);

        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $content = str_replace('<?php echo $name ?>', $contentChild, $content);

        static::assertEquals($content, $result);
    }

    public function testCanRenderWithCustomDefaultSuffix(): void
    {
        $name     = 'laminas-custom-suffix';
        $suffix   = 'pht';
        $renderer = new LaminasViewRenderer(null, null, $suffix);
        $renderer->addPath(__DIR__ . '/TestAsset');
        $result  = $renderer->render('laminasview-custom-suffix', ['name' => $name]);
        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview-custom-suffix.' . $suffix);
        $content = str_replace('<?php echo $name ?>', $name, $content);
        $this->assertEquals($content, $result);
    }

    public function testChangeLayoutInTemplate(): void
    {
        $renderer = new LaminasViewRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');

        $result = $renderer->render('laminasview-change-layout', ['layout' => 'laminasview-layout']);

        $contentChild = file_get_contents(__DIR__ . '/TestAsset/laminasview-change-layout.phtml');
        $contentChild = str_replace("<?php \$this->layout('laminasview-layout2'); ?>\n", '', $contentChild);

        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview-layout2.phtml');
        $content = str_replace("<?= \$this->content ?>\n", $contentChild, $content);

        static::assertEquals($content, $result);
    }
}
