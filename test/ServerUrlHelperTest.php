<?php

/**
 * @see       https://github.com/mezzio/mezzio-laminasviewrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-laminasviewrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-laminasviewrenderer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\LaminasView;

use Mezzio\Helper\ServerUrlHelper as BaseHelper;
use Mezzio\LaminasView\ServerUrlHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

use function PHPUnit\Framework\identicalTo;

class ServerUrlHelperTest extends TestCase
{
    /** @var ServerUrlHelper */
    private $helper;
    /** @var BaseHelper|MockObject */
    private $baseHelper;

    protected function setUp(): void
    {
        $this->baseHelper = $this->createMock(BaseHelper::class);
        $this->helper     = new ServerUrlHelper($this->baseHelper);
    }

    public function testInvocationProxiesToBaseHelper(): void
    {
        $this->baseHelper
            ->expects(self::once())
            ->method('generate')
            ->with('/foo')
            ->willReturn('https://example.com/foo');

        $this->assertEquals('https://example.com/foo', ($this->helper)('/foo'));
    }

    public function testSetUriProxiesToBaseHelper(): void
    {
        $uri = $this->createMock(UriInterface::class);
        $this->baseHelper
            ->expects(self::once())
            ->method('setUri')
            ->with(identicalTo($uri));

        $this->helper->setUri($uri);
    }
}
