<?php

declare(strict_types=1);

namespace MezzioTest\LaminasView;

use Generator;
use Mezzio\LaminasView\Exception\ExceptionInterface;
use Mezzio\Template\Exception\ExceptionInterface as TemplateExceptionInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function basename;
use function glob;
use function is_a;
use function substr;

class ExceptionTest extends TestCase
{
    public function testExceptionInterfaceExtendsTemplateExceptionInterface(): void
    {
        $this->assertTrue(is_a(ExceptionInterface::class, TemplateExceptionInterface::class, true));
    }

    /** @return Generator<string, array{0: string}> */
    public static function exception(): Generator
    {
        $exceptions = glob(__DIR__ . '/../src/Exception/*.php');
        foreach ($exceptions as $exception) {
            $class = substr(basename($exception), 0, -4);

            yield $class => ['Mezzio\LaminasView\Exception\\' . $class];
        }
    }

    #[DataProvider('exception')]
    public function testExceptionIsInstanceOfExceptionInterface(string $exception): void
    {
        $this->assertStringContainsString('Exception', $exception);
        $this->assertTrue(is_a($exception, ExceptionInterface::class, true));
    }
}
