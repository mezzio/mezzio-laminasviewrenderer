<?php

declare(strict_types=1);

namespace Mezzio\LaminasView;

use Psr\Container\ContainerInterface;

class NamespacedPathStackResolverFactory
{
    public function __invoke(ContainerInterface $container): NamespacedPathStackResolver
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $config = $config['templates'] ?? [];

        $resolver = new NamespacedPathStackResolver();
        if (! empty($config['default_suffix'])) {
            $resolver->setDefaultSuffix($config['default_suffix']);
        }

        return $resolver;
    }
}
