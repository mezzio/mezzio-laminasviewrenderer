<?php

declare(strict_types=1);

namespace Mezzio\LaminasView;

use Laminas\ServiceManager\ServiceManager;
use Laminas\View\HelperPluginManager;
use Mezzio\Template\TemplateRendererInterface;

/** @psalm-import-type ServiceManagerConfiguration from ServiceManager */
final class ConfigProvider
{
    /**
     * @return array{
     *     dependencies: ServiceManagerConfiguration,
     *     templates: array<string, mixed>,
     * }
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'templates'    => $this->getTemplates(),
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
                HelperPluginManager::class => HelperPluginManagerFactory::class,
                LaminasViewRenderer::class => LaminasViewRendererFactory::class,
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function getTemplates(): array
    {
        return [
            'extension' => 'phtml',
            'layout'    => 'layout::default',
            'paths'     => [],
        ];
    }
}
