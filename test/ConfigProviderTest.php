<?php

declare(strict_types=1);

namespace MezzioTest\LaminasView;

use Mezzio\LaminasView\ConfigProvider;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    public function testReturnedArrayContainsDependencies(): void
    {
        $config = (new ConfigProvider())->__invoke();

        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey('templates', $config);
        $this->assertIsArray($config['dependencies']);
    }
}
