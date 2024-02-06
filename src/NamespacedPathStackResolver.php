<?php

declare(strict_types=1);

namespace Mezzio\LaminasView;

use Laminas\View\Exception as ViewException;
use Laminas\View\Renderer\RendererInterface;
use Laminas\View\Resolver\TemplatePathStack;
use Laminas\View\Stream;
use SplFileInfo;
use SplStack;
use Traversable;

use function array_key_exists;
use function count;
use function file_exists;
use function get_debug_type;
use function gettype;
use function in_array;
use function ini_get;
use function is_array;
use function is_string;
use function iterator_to_array;
use function pathinfo;
use function preg_match;
use function sprintf;
use function str_starts_with;
use function stream_get_wrappers;
use function stream_wrapper_register;

use const PATHINFO_EXTENSION;

/**
 * Variant of TemplatePathStack providing namespaced paths.
 *
 * Allows adding paths by namespace. When resolving a template, if a namespace
 * is provided, it will search first on paths with that namespace, and fall
 * back to those provided without a namespace (or with the the __DEFAULT__
 * namespace).
 *
 * Namespaces are specified with a `namespace::` prefix when specifying the
 * template.
 *
 * Stream wrappers are deprecated and will be removed in 3.0
 *
 * @psalm-import-type PathStack from TemplatePathStack
 */
class NamespacedPathStackResolver extends TemplatePathStack
{
    public const DEFAULT_NAMESPACE = '__DEFAULT__';

    /**
     * @var array<string, PathStack>
     * @psalm-suppress NonInvariantDocblockPropertyType
     */
    protected $paths = [];

    /**
     * Constructor
     *
     * Overrides parent constructor to allow specifying paths as an associative
     * array.
     *
     * @param iterable<string, mixed>|null $options
     */
    public function __construct(?iterable $options = null)
    {
        $this->useViewStream = (bool) ini_get('short_open_tag');
        if ($this->useViewStream) {
            if (! in_array('laminas.view', stream_get_wrappers())) {
                stream_wrapper_register('laminas.view', Stream::class);
            }
        }

        if (null !== $options) {
            $this->setOptions($options);
        }
    }

    /**
     * Add a path to the stack with the given namespace.
     *
     * @param string $path
     * @throws ViewException\InvalidArgumentException For an invalid path.
     * @throws ViewException\InvalidArgumentException For an invalid namespace.
     */
    public function addPath($path, ?string $namespace = self::DEFAULT_NAMESPACE): void
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if (! is_string($path)) {
            throw new ViewException\InvalidArgumentException(sprintf(
                'Invalid path provided; expected a string, received %s',
                gettype($path)
            ));
        }

        if (null === $namespace) {
            $namespace = self::DEFAULT_NAMESPACE;
        }

        if ($namespace === '') {
            throw new ViewException\InvalidArgumentException(
                'Invalid namespace provided; must be a non-empty string'
            );
        }

        if (! array_key_exists($namespace, $this->paths)) {
            /** @psalm-var PathStack $splStack */
            $splStack                = new SplStack();
            $this->paths[$namespace] = $splStack;
        }

        $this->paths[$namespace]->push(static::normalizePath($path));
    }

    /**
     * Add many paths to the stack at once.
     *
     * @param array<string, string> $paths
     * @psalm-suppress ImplementedParamTypeMismatch, ImplementedReturnTypeMismatch
     */
    public function addPaths(array $paths): void
    {
        foreach ($paths as $namespace => $path) {
            /** @psalm-suppress DocblockTypeContradiction */
            if (! is_string($namespace)) {
                $namespace = self::DEFAULT_NAMESPACE;
            }

            $this->addPath($path, $namespace);
        }
    }

    /**
     * Overwrite all existing paths with the provided paths.
     *
     * This method should return $this to match parent class but it does not.
     *
     * @param  SplStack|array<string, string> $paths
     * @psalm-param PathStack|array<string, string> $paths
     * @psalm-suppress ImplementedParamTypeMismatch, ImplementedReturnTypeMismatch
     * @throws ViewException\InvalidArgumentException For invalid path types.
     */
    public function setPaths($paths): void
    {
        if ($paths instanceof Traversable) {
            $paths = iterator_to_array($paths, true);
        }

        /** @psalm-suppress DocblockTypeContradiction */
        if (! is_array($paths)) {
            throw new ViewException\InvalidArgumentException(sprintf(
                'Invalid paths provided; must be an array or Traversable, received %s',
                get_debug_type($paths),
            ));
        }

        /** @psalm-var array<string, string> $paths */

        $this->clearPaths();
        $this->addPaths($paths);
    }

    /**
     * Clear all paths.
     */
    public function clearPaths(): void
    {
        $this->paths = [];
    }

    /**
     * Retrieve the filesystem path to a view script
     *
     * @param string $name
     * @throws ViewException\DomainException
     */
    public function resolve($name, ?RendererInterface $renderer = null): ?string
    {
        $namespace = self::DEFAULT_NAMESPACE;
        $template  = $name;
        if (preg_match('#^(?P<namespace>[^:]+)::(?P<template>.*)$#', $template, $matches)) {
            $namespace = $matches['namespace'];
            $template  = $matches['template'];
        }

        $this->lastLookupFailure = false;

        if ($this->isLfiProtectionOn() && preg_match('#\.\.[\\\/]#', $template)) {
            throw new ViewException\DomainException(
                'Requested scripts may not include parent directory traversal ("../", "..\\" notation)'
            );
        }

        if (! count($this->paths)) {
            $this->lastLookupFailure = TemplatePathStack::FAILURE_NO_PATHS;
            return null;
        }

        // Ensure we have the expected file extension
        $defaultSuffix = $this->getDefaultSuffix();
        if (pathinfo($template, PATHINFO_EXTENSION) === '') {
            $template .= '.' . $defaultSuffix;
        }

        $path = null;
        if ($namespace !== self::DEFAULT_NAMESPACE) {
            $path = $this->getPathFromNamespace($template, $namespace);
        }

        $path ??= $this->getPathFromNamespace($template, self::DEFAULT_NAMESPACE);

        if ($path !== null) {
            return $path;
        }

        $this->lastLookupFailure = TemplatePathStack::FAILURE_NOT_FOUND;
        return null;
    }

    /**
     * Fetch a template path from a given namespace.
     *
     * @return null|string String path on success; null on failure
     */
    private function getPathFromNamespace(string $template, string $namespace): ?string
    {
        if (! array_key_exists($namespace, $this->paths)) {
            return null;
        }

        foreach ($this->paths[$namespace] as $path) {
            $file = new SplFileInfo($path . $template);
            if ($file->isReadable()) {
                // Found! Return it.
                if (($filePath = $file->getRealPath()) === false && str_starts_with($path, 'phar://')) {
                    // Do not try to expand phar paths (realpath + phars == fail)
                    $filePath = $path . $template;
                    if (! file_exists($filePath)) {
                        break;
                    }
                }

                if ($this->useStreamWrapper()) {
                    // If using a stream wrapper, prepend the spec to the path
                    $filePath = 'laminas.view://' . $filePath;
                }
                return $filePath;
            }
        }

        return null;
    }
}
