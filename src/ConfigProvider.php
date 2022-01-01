<?php

declare(strict_types=1);

namespace Mezzio\LaminasView;

use Laminas\View\HelperPluginManager;
use Mezzio\Template\TemplateRendererInterface;
use Zend\Expressive\ZendView\ZendViewRenderer;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'templates'    => $this->getTemplates(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'aliases'   => [
                TemplateRendererInterface::class => LaminasViewRenderer::class,

                // Legacy Zend Framework aliases
                \Zend\Expressive\Template\TemplateRendererInterface::class => TemplateRendererInterface::class,
                \Zend\View\HelperPluginManager::class                      => HelperPluginManager::class,
                ZendViewRenderer::class                                    => LaminasViewRenderer::class,
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
