<?php

/**
 * @see       https://github.com/mezzio/mezzio-laminasviewrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-laminasviewrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-laminasviewrenderer/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\LaminasView;

use Laminas\View\Helper\AbstractHelper;
use Mezzio\Helper\UrlHelper as BaseHelper;

class UrlHelper extends AbstractHelper
{
    /**
     * @var BaseHelper
     */
    private $helper;

    /**
     * @param BaseHelper $helper
     */
    public function __construct(BaseHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Proxies to `Mezzio\Helper\UrlHelper::generate()`
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    public function __invoke($route = null, $params = [])
    {
        return $this->helper->generate($route, $params);
    }
}
