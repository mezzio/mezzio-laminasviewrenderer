<?php

declare(strict_types=1);

namespace Mezzio\LaminasView;

use Laminas\View\Helper\AbstractHelper;
use Laminas\View\Helper\DeprecatedAbstractHelperHierarchyTrait;
use Mezzio\Helper\UrlHelper as BaseHelper;

/**
 * @final
 * @psalm-import-type UrlGeneratorOptions from BaseHelper
 */
class UrlHelper extends AbstractHelper
{
    use DeprecatedAbstractHelperHierarchyTrait;

    public function __construct(private BaseHelper $helper)
    {
    }

    /**
     * Proxies to `Mezzio\Helper\UrlHelper::generate()`
     *
     * @param array<string, mixed> $routeParams
     * @param array<string, mixed> $queryParams
     * @param array<string, mixed> $options Can have the following keys:
     *     - router (array): contains options to be passed to the router
     *     - reuse_result_params (bool): indicates if the current RouteResult
     *       parameters will be used, defaults to true
     * @psalm-param UrlGeneratorOptions $options
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
