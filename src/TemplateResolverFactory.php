<?php

declare(strict_types=1);

namespace Mezzio\LaminasView;

use Laminas\View\Resolver\AggregateResolver;
use Laminas\View\Resolver\ResolverInterface;
use Laminas\View\Resolver\TemplateMapResolver;
use Psr\Container\ContainerInterface;

/**
 * @psalm-internal Mezzio\LaminasView
 * @psalm-internal MezzioTest\LaminasView
 */
final class TemplateResolverFactory
{
    public function __invoke(ContainerInterface $container): ResolverInterface
    {
        return new AggregateResolver([
            $container->get(TemplateMapResolver::class),
            $container->get(NamespacedPathStackResolver::class),
        ]);
    }
}
