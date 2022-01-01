<?php

declare(strict_types=1);

namespace Mezzio\LaminasView;

use Laminas\View\Helper\AbstractHelper;
use Mezzio\Helper\ServerUrlHelper as BaseHelper;
use Psr\Http\Message\UriInterface;

/**
 * Alternate ServerUrl helper for use in Mezzio.
 */
class ServerUrlHelper extends AbstractHelper
{
    /** @var BaseHelper */
    private $helper;

    public function __construct(BaseHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Return a path relative to the current request URI.
     *
     * Proxies to `Mezzio\Helper\ServerUrlHelper::generate()`.
     */
    public function __invoke(?string $path = null): string
    {
        return $this->helper->generate($path);
    }

    /**
     * Proxies to `Mezzio\Helper\ServerUrlHelper::setUri()`
     */
    public function setUri(UriInterface $uri): void
    {
        $this->helper->setUri($uri);
    }
}
