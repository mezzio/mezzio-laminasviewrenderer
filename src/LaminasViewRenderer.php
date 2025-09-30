<?php

declare(strict_types=1);

namespace Mezzio\LaminasView;

use Laminas\View\Exception\RenderingFailedException;
use Laminas\View\Model\ModelInterface;
use Laminas\View\Model\ViewModel;
use Laminas\View\View;
use Mezzio\Template\ArrayParametersTrait;
use Mezzio\Template\DefaultParamsTrait;
use Mezzio\Template\Exception\InvalidArgumentException;
use Mezzio\Template\TemplateRendererInterface;

use function is_string;
use function sprintf;

/**
 * Template implementation bridging laminas/laminas-view.
 *
 * This implementation provides additional capabilities.
 *
 * First, it always ensures the resolver is an AggregateResolver, pushing any
 * non-Aggregate into a new AggregateResolver instance. Additionally, it always
 * registers a NamespacedPathStackResolver at priority 0 (lower than
 * default) in the Aggregate to ensure we can add and resolve namespaced paths.
 */
final class LaminasViewRenderer implements TemplateRendererInterface
{
    use ArrayParametersTrait;
    use DefaultParamsTrait;

    private ModelInterface|null $layout;

    /**
     * @throws InvalidArgumentException When $layout is an empty string.
     */
    public function __construct(
        private readonly View $view,
        string|ModelInterface|null $layout,
    ) {
        if ($layout === '') {
            throw new InvalidArgumentException(sprintf(
                'Layout must be a non-empty-string or a %s instance.',
                ModelInterface::class,
            ));
        }

        if (is_string($layout)) {
            $model = new ViewModel();
            $model->setTemplate($layout);
            $layout = $model;
        }

        $this->layout = $layout;

        $this->view->registerPreRenderHandler(function (ModelInterface $model): ModelInterface {
            $template = $model->getTemplate();
            if ($template === '') {
                throw RenderingFailedException::becauseATemplateWasNotSpecified();
            }

            return $this->mergeViewModel($template, $model);
        });
    }

    /**
     * Render a template with the given parameters.
     *
     * If a layout was specified during construction, it will be used;
     * alternately, you can specify a layout to use via the "layout"
     * parameter/variable, using either:
     *
     * - a string layout template name
     * - a Laminas\View\Model\ModelInterface instance
     *
     * Layouts specified with $params take precedence over layouts passed to
     *
     * @param non-empty-string $name
     * @param array|ModelInterface|object|null $params
     */
    public function render(string $name, $params = []): string
    {
        $viewModel = $params instanceof ModelInterface
            ? $params
            : new ViewModel($this->normalizeParamsAsMap($params), $name);

        $viewModel = $this->mergeViewModel($name, $viewModel);

        if ($viewModel->getVariable('layout') !== false) {
            $viewModel = $this->prepareLayout($viewModel);
        }

        return $this->view->renderLayout($viewModel);
    }

    /**
     * Merge global/template parameters with provided view model.
     *
     * @param non-empty-string $name Template name.
     */
    private function mergeViewModel(string $name, ModelInterface $model): ModelInterface
    {
        $model->setVariables($this->mergeParams(
            $name,
            $model->getVariables(),
        ));

        $model->setTemplate($name);

        return $model;
    }

    /**
     * Prepare the layout, if any.
     *
     * Injects the view model in the layout view model, if present.
     *
     * If the view model contains a non-empty 'layout' variable, that value
     * will be used to seed a layout view model, if:
     *
     * - it is a string layout template name
     * - it is a ModelInterface instance
     *
     * If a layout is discovered in this way, it will override the one set in
     * the constructor, if any.
     *
     * Returns the provided $viewModel unchanged if no layout is discovered;
     * otherwise, a view model representing the layout, with the provided
     * view model as a child, is returned.
     */
    private function prepareLayout(ModelInterface $viewModel): ModelInterface
    {
        /** @psalm-var mixed $providedLayout */
        $providedLayout = $viewModel->getVariable('layout', null);
        if (is_string($providedLayout) && ! empty($providedLayout)) {
            $layout = new ViewModel();
            $layout->setTemplate($providedLayout);
            $viewModel->setVariable('layout', null);
        } elseif ($providedLayout instanceof ModelInterface) {
            $layout = $providedLayout;
            $viewModel->setVariable('layout', null);
        } else {
            $layout = $this->layout ? clone $this->layout : null;
        }

        if ($layout instanceof ModelInterface) {
            $layout->addChild($viewModel);

            return $layout;
        }

        return $viewModel;
    }
}
