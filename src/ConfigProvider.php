<?php

declare(strict_types=1);

namespace Mezzio\LaminasView;

use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\Resolver\AggregateResolver;
use Mezzio\Template\TemplateRendererInterface;

/** @psalm-import-type ServiceManagerConfiguration from ServiceManager */
final class ConfigProvider
{
    /**
     * @return array{
     *     dependencies: ServiceManagerConfiguration,
     *     view_helpers: ServiceManagerConfiguration,
     *     templates: array{
     *         extension?: string,
     *         layout?: string,
     *         paths?: array<array-key, string|list<string>>,
     *         map?: array<string, string>,
     *     },
     * }
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'view_helpers' => $this->viewHelpers(),
            'templates'    => [
                /**
                 * Default template filename extension
                 */
                // 'extension' => 'phtml',

                /**
                 * The default layout template name
                 */
                // 'layout'    => null,

                /**
                 * Namespaced Template Paths
                 *
                 * 'paths' => [
                 *     'ns1' => '/some/ns1/directory',
                 *     'ns2' => [
                 *         '/some/ns2/directory',
                 *         '/another/ns2/directory',
                 *     ],
                 *     '/some/path/in-the-default-namespace/',
                 * ],
                 */
                'paths' => [],

                /**
                 * Template Map - the most performant way of registering templates
                 *
                 * keys are template names and values are file paths.
                 */
                'map' => [],
            ],
        ];
    }

    /** @return ServiceManagerConfiguration */
    public function getDependencies(): array
    {
        return [
            'aliases'   => [
                TemplateRendererInterface::class => LaminasViewRenderer::class,
            ],
            'factories' => [
                LaminasViewRenderer::class         => LaminasViewRendererFactory::class,
                NamespacedPathStackResolver::class => NamespacedPathStackResolverFactory::class,
                AggregateResolver::class           => TemplateResolverFactory::class,
            ],
        ];
    }

    /** @return ServiceManagerConfiguration */
    private function viewHelpers(): array
    {
        return [
            'factories' => [
                LayoutHelper::class    => InvokableFactory::class,
                ServerUrlHelper::class => ServerUrlHelperFactory::class,
                UrlHelper::class       => UrlHelperFactory::class,
            ],
            'aliases'   => [
                'layout'    => LayoutHelper::class,
                'serverUrl' => ServerUrlHelper::class,
                'url'       => UrlHelper::class,
            ],
        ];
    }
}
