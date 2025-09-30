<?php

declare(strict_types=1);

namespace Mezzio\LaminasView;

use Laminas\View\Exception as ViewException;
use Laminas\View\Resolver\ResolverInterface;
use Laminas\View\Resolver\TemplatePathStack;
use Override;

use function is_string;
use function preg_match;

/**
 * A template resolver providing namespaced paths.
 *
 * Allows adding paths by namespace. When resolving a template, if a namespace
 * is provided, it will search first on paths with that namespace, and fall
 * back to those provided without a namespace (or with the __DEFAULT__
 * namespace).
 *
 * Namespaces are specified with a `namespace::` prefix when specifying the
 * template.
 *
 * @psalm-type Options = array{
 *     lfi_protection?: bool,
 *     script_paths?: array<array-key, string|list<string>>,
 *     default_suffix?: non-empty-string,
 * }
 * @psalm-import-type Options from TemplatePathStack as StackOptions
 */
final class NamespacedPathStackResolver implements ResolverInterface
{
    private const DEFAULT_NAMESPACE = '__DEFAULT__';

    /**
     * A map of TemplatePathStack instances where the key is a namespace
     *
     * @var array<string, TemplatePathStack>
     */
    private array $resolvers = [];

    /**
     * Options passed to `TemplatePathStack` resolvers when they are instantiated
     *
     * @see TemplatePathStack::__construct()
     *
     * @var StackOptions
     */
    private array $resolverOptions;

    /** @param Options $options */
    public function __construct(array $options = [])
    {
        $paths = $options['script_paths'] ?? null;

        if (isset($options['script_paths'])) {
            unset($options['script_paths']);
        }

        /** @psalm-var StackOptions $options - Psalm cannot infer this is the correct type now script_paths is unset */
        $this->resolverOptions = $options;

        if ($paths === null) {
            return;
        }

        foreach ($paths as $ns => $listOrString) {
            $ns = is_string($ns) ? $ns : null;
            foreach ((array) $listOrString as $path) {
                $this->addPath($path, $ns);
            }
        }
    }

    /**
     * Add a path to the stack with the given namespace
     *
     * @throws ViewException\InvalidArgumentException For an invalid path.
     * @throws ViewException\InvalidArgumentException For an invalid namespace.
     */
    public function addPath(string $path, string|null $namespace = self::DEFAULT_NAMESPACE): void
    {
        $namespace ??= self::DEFAULT_NAMESPACE;

        if ($namespace === '') {
            throw new ViewException\InvalidArgumentException(
                'Invalid namespace provided; must be a non-empty string',
            );
        }

        if ($path === '') {
            throw new ViewException\InvalidArgumentException(
                'Invalid path provided; must be a non-empty string',
            );
        }

        $resolver = $this->getNamespace($namespace);
        $resolver->addPath($path);
    }

    /** @param non-empty-string $namespace */
    private function getNamespace(string $namespace): TemplatePathStack
    {
        $resolver = $this->resolvers[$namespace] ?? null;
        if ($resolver === null) {
            $resolver                    = new TemplatePathStack($this->resolverOptions);
            $this->resolvers[$namespace] = $resolver;
        }

        return $resolver;
    }

    /**
     * Add many paths to the stack at once.
     *
     * @param array<string, string> $paths
     */
    public function addPaths(array $paths): void
    {
        foreach ($paths as $namespace => $path) {
            $this->addPath($path, $namespace);
        }
    }

    /**
     * Overwrite all existing paths with the provided paths.
     *
     * This method should return $this to match parent class but it does not.
     *
     * @param array<string, string> $paths
     */
    public function setPaths(array $paths): void
    {
        $this->clearPaths();
        $this->addPaths($paths);
    }

    /**
     * Clear all paths.
     */
    public function clearPaths(): void
    {
        $this->resolvers = [];
    }

    #[Override]
    public function resolve(string $name): string|false
    {
        $namespace = self::DEFAULT_NAMESPACE;
        $template  = $name;
        if (preg_match('#^(?P<namespace>[^:]+)::(?P<template>.*)$#', $template, $matches)) {
            $namespace = $matches['namespace'];
            $template  = $matches['template'];
        }

        if ($namespace === '' || $template === '') {
            return false;
        }

        $resolver = $this->getNamespace($namespace);

        return $resolver->resolve($template);
    }
}
