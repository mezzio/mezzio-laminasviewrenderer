<?php

declare(strict_types=1);

namespace Mezzio\LaminasView;

use Laminas\View\Helper\StatefulHelperInterface;
use Laminas\View\Model\ModelInterface;
use Laminas\View\Model\ViewModel;

final class LayoutHelper implements StatefulHelperInterface
{
    private ModelInterface|null $layout = null;

    /**
     * Set layout template or retrieve "layout" view model
     *
     * If no arguments are given, returns a view model that will be used to manipulate the layout
     *
     * @param null|string $template Provide a template name to set that template as the current layout.
     *                              An empty string removes the current template.
     * @return ($template is null ? ModelInterface : self)
     */
    public function __invoke(string|null $template = null): ModelInterface|self
    {
        if (! $this->layout instanceof ModelInterface) {
            $this->layout = new ViewModel();
        }

        if ($template === null) {
            return $this->layout;
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        $this->layout->setTemplate($template);

        return $this;
    }

    public function resetState(): void
    {
        $this->layout = null;
    }
}
