<?php

declare(strict_types=1);

namespace Mezzio\LaminasView;

use Interop\Container\ContainerInterface as InteropContainerInterface;
use Laminas\View\HelperPluginManager;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\View\Resolver;
use Mezzio\Helper\ServerUrlHelper as BaseServerUrlHelper;
use Mezzio\Helper\UrlHelper as BaseUrlHelper;
use Psr\Container\ContainerInterface;

use function get_class;
use function is_array;
use function is_numeric;
use function sprintf;

/**
 * Create and return a LaminasView template instance.
 *
 * Requires the Mezzio\Router\RouterInterface service (for creating
 * the UrlHelper instance).
 *
 * Optionally requires the Laminas\View\HelperPluginManager service; if present,
 * will use the service to inject the PhpRenderer instance.
 *
 * Optionally uses the service 'config', which should return an array. This
 * factory consumes the following structure:
 *
 * <code>
 * 'templates' => [
 *     'extension' => 'default template file extension',
 *     'layout' => 'name of layout view to use, if any',
 *     'map'    => [
 *         // template => filename pairs
 *     ],
 *     'paths'  => [
 *         // namespace / path pairs
 *         //
 *         // Numeric namespaces imply the default/main namespace. Paths may be
 *         // strings or arrays of string paths to associate with the namespace.
 *     ],
 * ]
 * </code>
 *
 * Injects the HelperPluginManager used by the PhpRenderer with mezzio
 * overrides of the url and serverurl helpers.
 */
class LaminasViewRendererFactory
{
    public function __invoke(ContainerInterface $container): LaminasViewRenderer
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $config = $config['templates'] ?? [];

        // Configuration
        $resolver = new Resolver\AggregateResolver();
        $resolver->attach(
            new Resolver\TemplateMapResolver($config['map'] ?? []),
            100
        );

        // Create or retrieve the renderer from the container
        $renderer = $container->has(PhpRenderer::class)
            ? $container->get(PhpRenderer::class)
            : ($container->has(\Zend\View\Renderer\PhpRenderer::class)
                ? $container->get(\Zend\View\Renderer\PhpRenderer::class)
                : new PhpRenderer());
        $renderer->setResolver($resolver);

        // Inject helpers
        $this->injectHelpers($renderer, $container);

        $defaultSuffix = $config['extension'] ?? $config['default_suffix'] ?? null;
        // Inject renderer
        $view = new LaminasViewRenderer($renderer, $config['layout'] ?? null, $defaultSuffix);

        // Add template paths
        $allPaths = isset($config['paths']) && is_array($config['paths']) ? $config['paths'] : [];
        foreach ($allPaths as $namespace => $paths) {
            $namespace = is_numeric($namespace) ? null : $namespace;
            foreach ((array) $paths as $path) {
                $view->addPath($path, $namespace);
            }
        }

        return $view;
    }

    /**
     * Inject helpers into the PhpRenderer instance.
     *
     * If a HelperPluginManager instance is present in the container, uses that;
     * otherwise, instantiates one.
     *
     * In each case, injects with the custom url/serverurl implementations.
     *
     * @throws Exception\InvalidContainerException If the $container argument
     *     does not implement InteropContainerInterface.
     * @throws Exception\MissingHelperException
     */
    private function injectHelpers(PhpRenderer $renderer, ContainerInterface $container): void
    {
        $helpers = $this->retrieveHelperManager($container);
        $helpers->setAlias('url', BaseUrlHelper::class);
        $helpers->setAlias('Url', BaseUrlHelper::class);
        $helpers->setFactory(BaseUrlHelper::class, function () use ($container) {
            if (
                ! $container->has(BaseUrlHelper::class)
                && ! $container->has(\Zend\Expressive\Helper\UrlHelper::class)
            ) {
                throw new Exception\MissingHelperException(sprintf(
                    'An instance of %s is required in order to create the "url" view helper; not found',
                    BaseUrlHelper::class
                ));
            }
            return new UrlHelper(
                $container->has(BaseUrlHelper::class)
                    ? $container->get(BaseUrlHelper::class)
                    : $container->get(\Zend\Expressive\Helper\UrlHelper::class)
            );
        });

        $helpers->setAlias('serverurl', BaseServerUrlHelper::class);
        $helpers->setAlias('serverUrl', BaseServerUrlHelper::class);
        $helpers->setAlias('ServerUrl', BaseServerUrlHelper::class);
        $helpers->setFactory(BaseServerUrlHelper::class, function () use ($container) {
            if (
                ! $container->has(BaseServerUrlHelper::class)
                && ! $container->has(\Zend\Expressive\Helper\ServerUrlHelper::class)
            ) {
                throw new Exception\MissingHelperException(sprintf(
                    'An instance of %s is required in order to create the "url" view helper; not found',
                    BaseServerUrlHelper::class
                ));
            }
            return new ServerUrlHelper(
                $container->has(BaseServerUrlHelper::class)
                    ? $container->get(BaseServerUrlHelper::class)
                    : $container->get(\Zend\Expressive\Helper\ServerUrlHelper::class)
            );
        });

        $renderer->setHelperPluginManager($helpers);
    }

    /**
     * @throws Exception\InvalidContainerException If the $container argument
     *     does not implement InteropContainerInterface.
     */
    private function retrieveHelperManager(ContainerInterface $container): HelperPluginManager
    {
        if ($container->has(HelperPluginManager::class)) {
            return $container->get(HelperPluginManager::class);
        }

        if ($container->has(\Zend\View\HelperPluginManager::class)) {
            return $container->get(\Zend\View\HelperPluginManager::class);
        }

        if (! $container instanceof InteropContainerInterface) {
            throw new Exception\InvalidContainerException(sprintf(
                '%s expects a %s instance to its constructor; however, your service'
                . ' container is an instance of %s, which does not implement that'
                . ' interface. Consider switching to laminas-servicemanager for your'
                . ' container implementation if you wish to use the laminas-view renderer.',
                HelperPluginManager::class,
                InteropContainerInterface::class,
                get_class($container)
            ));
        }

        return new HelperPluginManager($container);
    }
}
