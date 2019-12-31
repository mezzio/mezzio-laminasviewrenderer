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
     * @param string $routeName
     * @param array  $routeParams
     * @param array  $queryParams
     * @param string $fragmentIdentifier
     * @param array  $options Can have the following keys:
     *     - router (array): contains options to be passed to the router
     *     - reuse_result_params (bool): indicates if the current RouteResult
     *       parameters will be used, defaults to true
     * @return string
     */
    public function __invoke(
        $routeName = null,
        array $routeParams = [],
        array $queryParams = [],
        $fragmentIdentifier = '',
        array $options = []
    ) {
        return $this->helper->generate($routeName, $routeParams, $queryParams, $fragmentIdentifier, $options);
    }
}
