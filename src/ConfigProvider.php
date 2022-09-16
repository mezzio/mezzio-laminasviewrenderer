<?php

declare(strict_types=1);

namespace Mezzio\LaminasView;

use Laminas\ServiceManager\ConfigInterface;
use Laminas\View\HelperPluginManager;
use Mezzio\Template\TemplateRendererInterface;

/**
 * @psalm-import-type ServiceManagerConfigurationType from ConfigInterface
 */
class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'templates'    => $this->getTemplates(),
        ];
    }

    /** @return ServiceManagerConfigurationType */
    public function getDependencies(): array
    {
        return [
            'aliases'   => [
                TemplateRendererInterface::class => LaminasViewRenderer::class,

                // Legacy Zend Framework aliases
                'Zend\Expressive\Template\TemplateRendererInterface' => TemplateRendererInterface::class,
                'Zend\View\HelperPluginManager'                      => HelperPluginManager::class,
                'Zend\Expressive\ZendView\ZendViewRenderer'          => LaminasViewRenderer::class,
            ],
            'factories' => [
                HelperPluginManager::class => HelperPluginManagerFactory::class,
                LaminasViewRenderer::class => LaminasViewRendererFactory::class,
            ],
        ];
    }

    public function getTemplates(): array
    {
        return [
            'extension' => 'phtml',
            'layout'    => 'layout::default',
            'paths'     => [],
        ];
    }
}
