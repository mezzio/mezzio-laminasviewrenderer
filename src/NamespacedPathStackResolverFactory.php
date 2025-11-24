<?php

declare(strict_types=1);

namespace Mezzio\LaminasView;

use Psr\Container\ContainerInterface;

use function array_filter;
use function is_iterable;
use function is_string;
use function iterator_to_array;
use function reset;

/**
 * @internal
 *
 * @psalm-internal Mezzio\LaminasView
 * @psalm-internal MezzioTest\LaminasView
 */
final class NamespacedPathStackResolverFactory
{
    public function __invoke(ContainerInterface $container): NamespacedPathStackResolver
    {
        /** @psalm-var mixed $config */
        $config = $container->has('config') ? $container->get('config') : [];
        $config = is_iterable($config) ? iterator_to_array($config) : [];

        /**
         * There is some historic ambiguity around which key is used to set the default template suffix.
         *
         * Prefer the documented option: `templates.extension`, but also support `templates.default_suffix` and the
         * key documented for laminas-view of `view_manager.default_template_suffix`
         */
        $suffixes = array_filter([
            $config['templates']['extension'] ?? null,
            $config['templates']['default_suffix'] ?? null,
            $config['view_manager']['default_template_suffix'] ?? null,
        ], static fn (mixed $value): bool => is_string($value) && $value !== '');

        $suffix = reset($suffixes);
        $suffix = is_string($suffix) ? $suffix : 'phtml';

        /**
         * The `templates.paths` key is documented as a list OR a map containing either single path strings or a list
         * of paths.
         *
         * This key is specifically for Mezzio 'namespaced' paths which is exactly what this resolver is for.
         *
         * There is no runtime validation of the array here
         *
         * @var array<array-key, string|list<string>> $pathConfig
         */
        $pathConfig = $config['templates']['paths'] ?? [];

        return new NamespacedPathStackResolver([
            'default_suffix' => $suffix,
            'script_paths'   => $pathConfig,
        ]);
    }
}
