<?php

/**
 * @see       https://github.com/mezzio/mezzio-laminasviewrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-laminasviewrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-laminasviewrenderer/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\LaminasView;

use Laminas\View\Helper\AbstractHelper;
use Mezzio\Helper\ServerUrlHelper as BaseHelper;
use Psr\Http\Message\UriInterface;

/**
 * Alternate ServerUrl helper for use in Mezzio.
 */
class ServerUrlHelper extends AbstractHelper
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
     * Return a path relative to the current request URI.
     *
     * Proxies to `Mezzio\Helper\ServerUrlHelper::generate()`.
     *
     * @param null|string $path
     * @return string
     */
    public function __invoke($path = null)
    {
        return $this->helper->generate($path);
    }

    /**
     * Proxies to `Mezzio\Helper\ServerUrlHelper::setUri()`
     * @param UriInterface $uri
     * @return void
     */
    public function setUri(UriInterface $uri)
    {
        $this->helper->setUri($uri);
    }
}
