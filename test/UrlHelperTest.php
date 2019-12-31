<?php

/**
 * @see       https://github.com/mezzio/mezzio-laminasviewrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-laminasviewrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-laminasviewrenderer/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\LaminasView;

use Mezzio\Helper\UrlHelper as BaseHelper;
use Mezzio\LaminasView\UrlHelper;
use PHPUnit_Framework_TestCase as TestCase;

class UrlHelperTest extends TestCase
{
    public function setUp()
    {
        $this->baseHelper = $this->prophesize(BaseHelper::class);
    }

    public function createHelper()
    {
        return new UrlHelper($this->baseHelper->reveal());
    }

    public function testInvocationProxiesToBaseHelper()
    {
        $this->baseHelper->generate('resource', ['id' => 'sha1'], [], '', [])->willReturn('/resource/sha1');
        $helper = $this->createHelper();
        $this->assertEquals('/resource/sha1', $helper('resource', ['id' => 'sha1']));
    }

    public function testUrlHelperAcceptsQueryParametersFragmentAndOptions()
    {
        $this->baseHelper->generate(
            'resource',
            ['id' => 'sha1'],
            ['foo' => 'bar'],
            'fragment',
            ['reuse_result_params' => true]
        )->willReturn('PATH');
        $helper = $this->createHelper();
        $this->assertEquals(
            'PATH',
            $helper('resource', ['id' => 'sha1'], ['foo' => 'bar'], 'fragment', ['reuse_result_params' => true])
        );
    }
}
