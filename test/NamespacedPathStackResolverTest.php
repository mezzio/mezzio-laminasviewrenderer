<?php

declare(strict_types=1);

namespace MezzioTest\LaminasView;

use Laminas\View\Exception\InvalidArgumentException;
use Mezzio\LaminasView\NamespacedPathStackResolver;
use Mezzio\Template\TemplatePath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function array_find;

final class NamespacedPathStackResolverTest extends TestCase
{
    public function testTemplatePathsGivenViaTheConstructorWillYieldResolvingTemplates(): void
    {
        $resolver = new NamespacedPathStackResolver([
            'script_paths' => [
                'fred'  => __DIR__ . '/TestAsset/templates/namespaced/fred',
                'wilma' => __DIR__ . '/TestAsset/templates/namespaced/wilma',
            ],
        ]);

        self::assertFalse($resolver->resolve('barney::rubble'));
        self::assertNotFalse($resolver->resolve('fred::a'));
        self::assertNotFalse($resolver->resolve('wilma::b'));
    }

    public function testYouCannotAddAPathWithAnEmptyNamespace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid namespace provided; must be a non-empty string');

        (new NamespacedPathStackResolver())->addPath('/foo', '');
    }

    public function testYouCannotAddAPathWithAnEmptyPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid path provided; must be a non-empty string');

        (new NamespacedPathStackResolver())->addPath('');
    }

    public function testSetPathsWillClearExistingPaths(): void
    {
        $resolver = new NamespacedPathStackResolver([
            'script_paths' => [
                'fred'  => __DIR__ . '/TestAsset/templates/namespaced/fred',
                'wilma' => __DIR__ . '/TestAsset/templates/namespaced/wilma',
            ],
        ]);

        self::assertNotFalse($resolver->resolve('fred::a'), '"fred" namespace should be resolving correctly');
        self::assertFalse($resolver->resolve('foo::fred/a'), '"foo" namespace should not be resolvable yet');

        $resolver->setPaths([
            'foo' => __DIR__ . '/TestAsset/templates/namespaced',
        ]);

        self::assertNotFalse($resolver->resolve('foo::fred/a'), '"foo" namespace should now resolve');
        self::assertFalse($resolver->resolve('fred::a'), '"fred" namespace should no longer be resolvable');
    }

    /**
     * @return list<array{0: non-empty-string}>
     */
    public static function unresolvableTemplates(): array
    {
        return [
            ['::muppet::'],
            ['::miss-piggy'],
            ['cookie-monster::'],
            ['kermit'],
            ['muppets::not-there'],
        ];
    }

    /** @param non-empty-string $template */
    #[DataProvider('unresolvableTemplates')]
    public function testUnresolvableTemplatesAreIgnored(string $template): void
    {
        $resolver = new NamespacedPathStackResolver([
            'script_paths' => [
                'fred'  => __DIR__ . '/TestAsset/templates/namespaced/fred',
                'wilma' => __DIR__ . '/TestAsset/templates/namespaced/wilma',
            ],
        ]);

        self::assertFalse($resolver->resolve($template));
    }

    public function testPathsAddedWithoutANamespaceCanStillBeResolvedUsingTheDefaultNS(): void
    {
        $resolver = new NamespacedPathStackResolver([]);
        $resolver->addPath(__DIR__ . '/TestAsset/templates/namespaced/fred');

        self::assertNotFalse($resolver->resolve('a'));
        self::assertNotFalse($resolver->resolve('b'));
        self::assertFalse($resolver->resolve('c'));
    }

    public function testPathsAddedWithANullNamespaceCanStillBeResolvedUsingTheDefaultNS(): void
    {
        $resolver = new NamespacedPathStackResolver([]);
        $resolver->addPath(__DIR__ . '/TestAsset/templates/namespaced/fred', null);

        self::assertNotFalse($resolver->resolve('a'));
        self::assertNotFalse($resolver->resolve('b'));
        self::assertFalse($resolver->resolve('c'));
    }

    public function testPathRetrieval(): void
    {
        $resolver = new NamespacedPathStackResolver([
            'script_paths' => [
                'fred'  => __DIR__ . '/TestAsset/templates/namespaced/fred',
                'wilma' => __DIR__ . '/TestAsset/templates/namespaced/wilma',
            ],
        ]);

        $resolver->addPath(__DIR__ . '/TestAsset/templates');

        $paths = $resolver->getPathsForMezzioTemplateRenderer();
        self::assertCount(3, $paths);

        $findByNs = static function (TemplatePath $path, string|null $ns): bool {
            return $path->getNamespace() === $ns;
        };

        $fred    = array_find($paths, static fn (TemplatePath $path): bool => $findByNs($path, 'fred'));
        $wilma   = array_find($paths, static fn (TemplatePath $path): bool => $findByNs($path, 'wilma'));
        $default = array_find($paths, static fn (TemplatePath $path): bool => $findByNs($path, null));

        self::assertInstanceOf(TemplatePath::class, $fred);
        self::assertSame(__DIR__ . '/TestAsset/templates/namespaced/fred/', $fred->getPath());
        self::assertInstanceOf(TemplatePath::class, $wilma);
        self::assertSame(__DIR__ . '/TestAsset/templates/namespaced/wilma/', $wilma->getPath());
        self::assertInstanceOf(TemplatePath::class, $default);
        self::assertSame(__DIR__ . '/TestAsset/templates/', $default->getPath());
    }

    public function testNamespacesAreCaseSensitive(): void
    {
        $resolver = new NamespacedPathStackResolver([
            'script_paths' => [
                'FRED' => __DIR__ . '/TestAsset/templates/namespaced/fred',
                'fred' => __DIR__ . '/TestAsset/templates/namespaced/wilma',
            ],
        ]);

        self::assertNotFalse($resolver->resolve('FRED::a'));
        self::assertNotFalse($resolver->resolve('fred::a'));
        self::assertNotSame(
            $resolver->resolve('FRED::a'),
            $resolver->resolve('fred::a'),
        );
        self::assertNotFalse($resolver->resolve('FRED::fred'));
        self::assertFalse($resolver->resolve('fred::fred'));
    }

    public function testThereCanBeMultiplePathsPerNamespace(): void
    {
        $resolver = new NamespacedPathStackResolver([]);

        $resolver->addPath(__DIR__ . '/TestAsset/templates/namespaced/fred', 'ns');
        $resolver->addPath(__DIR__ . '/TestAsset/templates/namespaced/wilma', 'ns');

        self::assertNotFalse($resolver->resolve('ns::fred'));
        self::assertNotFalse($resolver->resolve('ns::wilma'));
    }
}
