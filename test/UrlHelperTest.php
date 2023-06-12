<?php

declare(strict_types=1);

namespace MezzioTest\LaminasView;

use Mezzio\Helper\UrlHelperInterface;
use Mezzio\LaminasView\UrlHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UrlHelperTest extends TestCase
{
    /** @var UrlHelperInterface&MockObject */
    private UrlHelperInterface $baseHelper;
    private UrlHelper $helper;

    protected function setUp(): void
    {
        $this->baseHelper = $this->createMock(UrlHelperInterface::class);
        $this->helper     = new UrlHelper($this->baseHelper);
    }

    public function testInvocationProxiesToBaseHelper(): void
    {
        $this->baseHelper
            ->expects(self::once())
            ->method('generate')
            ->with('resource', ['id' => 'sha1'], [], null, [])
            ->willReturn('/resource/sha1');
        $this->assertEquals('/resource/sha1', ($this->helper)('resource', ['id' => 'sha1']));
    }

    public function testUrlHelperAcceptsQueryParametersFragmentAndOptions(): void
    {
        $this->baseHelper
            ->expects(self::once())
            ->method('generate')
            ->with(
                'resource',
                ['id' => 'sha1'],
                ['foo' => 'bar'],
                'fragment',
                ['reuse_result_params' => true]
            )->willReturn('PATH');

        $this->assertEquals(
            'PATH',
            ($this->helper)('resource', ['id' => 'sha1'], ['foo' => 'bar'], 'fragment', ['reuse_result_params' => true])
        );
    }

    /**
     * In particular, the fragment identifier needs to be null.
     */
    public function testUrlHelperPassesExpectedDefaultsToBaseHelper(): void
    {
        $this->baseHelper
            ->expects(self::once())
            ->method('generate')
            ->with(null, [], [], null, [])
            ->willReturn('PATH');

        $this->assertEquals(
            'PATH',
            ($this->helper)()
        );
    }
}
