<?php

/**
 * @see       https://github.com/mezzio/mezzio-laminasviewrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-laminasviewrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-laminasviewrenderer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\LaminasView;

use Laminas\View\HelperPluginManager;
use Mezzio\Template\TemplateRendererInterface;

class ConfigProvider
{
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'templates' => $this->getTemplates(),
        ];
    }

    public function getDependencies() : array
    {
        return [
            'aliases' => [
                TemplateRendererInterface::class => LaminasViewRenderer::class,

                // Legacy Zend Framework aliases
                \Zend\Expressive\Template\TemplateRendererInterface::class => TemplateRendererInterface::class,
                \Zend\View\HelperPluginManager::class => HelperPluginManager::class,
                \Zend\Expressive\ZendView\ZendViewRenderer::class => LaminasViewRenderer::class,
            ],
            'factories' => [
                HelperPluginManager::class => HelperPluginManagerFactory::class,
                LaminasViewRenderer::class => LaminasViewRendererFactory::class,
            ],
        ];
    }

    public function getTemplates() : array
    {
        return [
            'paths' => [],
        ];
    }
}
