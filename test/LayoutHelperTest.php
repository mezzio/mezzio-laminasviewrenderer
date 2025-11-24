<?php

declare(strict_types=1);

namespace MezzioTest\LaminasView;

use Laminas\View\Model\ModelInterface;
use Mezzio\LaminasView\LayoutHelper;
use PHPUnit\Framework\TestCase;

final class LayoutHelperTest extends TestCase
{
    public function testAModelWillBeProducedWhenInvokeHasNoArguments(): void
    {
        $helper = new LayoutHelper();
        self::assertInstanceOf(ModelInterface::class, $helper->__invoke());
    }

    public function testTheTemplateWillBeSetOnTheModelWhenGiven(): void
    {
        $helper   = new LayoutHelper();
        $model    = $helper->__invoke();
        $self     = $helper->__invoke('foo');
        $newModel = $helper->__invoke();

        self::assertSame($helper, $self);
        self::assertInstanceOf(ModelInterface::class, $model);
        self::assertInstanceOf(ModelInterface::class, $newModel);
        self::assertSame($model, $newModel);
        self::assertSame('foo', $model->getTemplate());
    }

    public function testVariablesWillNotBeUnsetWhenChangingLayouts(): void
    {
        $helper = new LayoutHelper();
        $model  = $helper->__invoke();
        $model->setVariable('muppet', 'Kermit');
        $self     = $helper->__invoke('foo');
        $newModel = $helper->__invoke();

        self::assertSame($helper, $self);
        self::assertSame('Kermit', $newModel->getVariable('muppet'));
    }

    public function testTheLayoutTemplateCanBeSetToAnEmptyString(): void
    {
        $helper = new LayoutHelper();
        $helper->__invoke('foo');
        $helper->__invoke('');

        self::assertSame('', $helper->__invoke()->getTemplate());
    }

    public function testResetStateResetsTheViewModelReference(): void
    {
        $helper = new LayoutHelper();
        $helper->__invoke('foo');
        $initial = $helper->__invoke();
        self::assertSame('foo', $initial->getTemplate());

        $helper->resetState();

        $model = $helper->__invoke();
        self::assertSame('', $model->getTemplate());
        self::assertNotSame($initial, $model);
    }
}
