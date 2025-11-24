<?php

declare(strict_types=1);

namespace Mezzio\LaminasView;

use Laminas\View\Exception\RenderingFailedException;
use Laminas\View\HelperPluginManagerInterface;
use Laminas\View\Model\ModelInterface;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\RendererInterface;
use Mezzio\Template\ArrayParametersTrait;
use Mezzio\Template\DefaultParamsTrait;
use Mezzio\Template\Exception\InvalidArgumentException;
use Mezzio\Template\TemplateRendererInterface;

use function array_merge;
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
        private readonly RendererInterface $renderer,
        private readonly HelperPluginManagerInterface $helpers,
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
    }

    /**
     * Before rendering a model, merge in any default view variables
     */
    private function beforeRender(ModelInterface $model): ModelInterface
    {
        $template = $model->getTemplate();
        if ($template === '') {
            throw RenderingFailedException::becauseATemplateWasNotSpecified();
        }

        return $this->mergeViewModel($template, $model);
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

        $content = $this->renderRecursively($viewModel);
        $layout  = $this->prepareLayout($viewModel);

        if ($layout !== false) {
            $layout = $this->beforeRender($layout);
            $layout->setVariable('content', $content);
            $content = $this->renderer->render($layout);
        }

        $this->helpers->resetState();

        return $content;
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
    private function prepareLayout(ModelInterface $viewModel): ModelInterface|false
    {
        /** @psalm-var mixed $providedLayout */
        $providedLayout = $viewModel->getVariable('layout', null);

        /**
         * When the layout is explicitly given as false in the top-level view model, then layout will be disabled.
         */
        if ($providedLayout === false) {
            return false;
        }

        /**
         * In all other situations, layout is defined in the following order:
         *
         * - Layout defined by the layout view helper
         * - layout defined in the params of the given view model ($providedLayout)
         * - The default layout defined in $this->layout
         * - no layout
         */

        $helperLayout = $this->helpers->get(LayoutHelper::class)->__invoke();
        if ($helperLayout->getTemplate() !== '') {
            return $helperLayout;
        }

        $variables = $helperLayout->getVariables();

        if (is_string($providedLayout) && $providedLayout !== '') {
            return new ViewModel($variables, $providedLayout);
        }

        if ($providedLayout instanceof ModelInterface && $providedLayout->getTemplate() !== '') {
            return new ViewModel(array_merge(
                $providedLayout->getVariables(),
                $variables,
            ), $providedLayout->getTemplate());
        }

        if ($this->layout instanceof ModelInterface) {
            return new ViewModel(array_merge(
                $this->layout->getVariables(),
                $variables,
            ), $this->layout->getTemplate());
        }

        return false;
    }

    /** @throws RenderingFailedException When any exception occurs during render. */
    private function renderRecursively(ModelInterface $model): string
    {
        foreach ($model->getChildren() as $child) {
            $content = $this->renderRecursively($child);
            if ($child->isAppend()) {
                /** @psalm-var mixed $existingContent */
                $existingContent = $model->getVariable($child->captureTo(), '');
                $existingContent = is_string($existingContent)
                    ? $existingContent
                    : '';

                $content = $existingContent . $content;
            }

            $model->setVariable($child->captureTo(), $content);
        }

        return $this->renderer->render($this->beforeRender($model));
    }
}
