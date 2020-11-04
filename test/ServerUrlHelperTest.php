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
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ProphecyInterface;
use Psr\Http\Message\UriInterface;

class ServerUrlHelperTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var BaseHelper|ProphecyInterface
     */
    private $baseHelper;

    public function setUp(): void
    {
        $this->baseHelper = $this->prophesize(BaseHelper::class);
    }

    public function createHelper()
    {
        return new ServerUrlHelper($this->baseHelper->reveal());
    }

    public function testInvocationProxiesToBaseHelper()
    {
        $this->baseHelper->generate('/foo')->willReturn('https://example.com/foo');
        $helper = $this->createHelper();
        $this->assertEquals('https://example.com/foo', $helper('/foo'));
    }

    public function testSetUriProxiesToBaseHelper()
    {
        $uri = $this->prophesize(UriInterface::class);
        $this->baseHelper->setUri($uri->reveal())->shouldBeCalled();
        $helper = $this->createHelper();
        $helper->setUri($uri->reveal());
    }
}
