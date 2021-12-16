<?php

declare(strict_types=1);

namespace Mezzio\LaminasView;

use Laminas\View\Helper\AbstractHelper;
use Mezzio\Helper\UrlHelper as BaseHelper;

class UrlHelper extends AbstractHelper
{
    /** @var BaseHelper */
    private $helper;

    public function __construct(BaseHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Proxies to `Mezzio\Helper\UrlHelper::generate()`
     *
     * @param array $options Can have the following keys:
     *     - router (array): contains options to be passed to the router
     *     - reuse_result_params (bool): indicates if the current RouteResult
     *       parameters will be used, defaults to true
     * @return string
     */
    public function __invoke(
        ?string $routeName = null,
        array $routeParams = [],
        array $queryParams = [],
        ?string $fragmentIdentifier = null,
        array $options = []
    ) {
        return $this->helper->generate($routeName, $routeParams, $queryParams, $fragmentIdentifier, $options);
    }
}
