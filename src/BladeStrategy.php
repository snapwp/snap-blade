<?php

namespace Snap\Blade;

use Snap\Http\Validation\Validator;
use Snap\Services\Config;
use Snap\Services\Request;
use Snap\Templating\TemplatingInterface;

/**
 * The Blade templating strategy.
 */
class BladeStrategy implements TemplatingInterface
{
    /**
     * The current view name being displayed.
     *
     * @var string|null
     */
    protected $current_view = null;

    /**
     * Snap_Blade instance.
     *
     * @var SnapBlade
     */
    private $blade;

    /**
     * BladeStrategy constructor.
     *
     * @param \Snap\Blade\SnapBlade $blade
     */
    public function __construct(SnapBlade $blade)
    {
        $this->blade = $blade;
    }

    /**
     * Renders a view.
     *
     * @param  string $slug The slug for the generic template.
     * @param  array  $data Optional. Additional data to pass to a partial. Available in the partial as $data.
     * @throws \Exception
     */
    public function render($slug, $data = [])
    {
        $this->current_view = $this->getTemplateName($slug);

        $data = $this->addDefaultData($data);

        echo $this->blade->run($this->current_view, $data);

        // Now a view has been rendered, reset the current_view context.
        $this->current_view = null;
    }

    /**
     * Fetch and display a template partial.
     *
     * @param  string $slug The slug for the generic template.
     * @param  array  $data Optional. Additional data to pass to a partial. Available in the partial as $data.
     * @throws \Exception
     */
    public function partial($slug, $data = [])
    {
        $data = $this->addDefaultData($data);

        // Check if this is being run outside of a view context.
        if ($this->current_view === null) {
            echo $this->blade->run('partials.' . $this->transformPath($slug), $data);
            return;
        }

        echo $this->blade->runChild('partials.' . $this->transformPath($slug), $data);
    }

    /**
     * Generate the template file name from the slug.
     *
     * @param  string $slug The slug for the generic template.
     * @return string
     */
    public function getTemplateName($slug)
    {
        $slug = \str_replace(
            [
                Config::get('theme.templates_directory') . '/',
                $this->blade->getFileExtension(),
            ],
            '',
            $slug
        );

        if (\strpos($slug, 'views/') !== 0) {
            $slug = 'views.' . $slug;
        }

        return $this->transformPath($slug);
    }

    /**
     * Returns the current view template name.
     *
     * @return string|null Returns null if called before a view has been dispatched.
     */
    public function getCurrentView(): ?string
    {
        return $this->current_view;
    }

    /**
     * Replace slashes with periods.
     *
     * @param  string $slug Template path to bladeify.
     * @return string
     */
    public function transformPath(string $slug): string
    {
        return \str_replace(['/', '\\'], '.', $slug);
    }

    /**
     * Add default data to template.
     *
     * @param array $data Data array.
     * @return array
     * @throws \Hodl\Exceptions\ContainerException
     * @throws \Hodl\Exceptions\NotFoundException
     */
    private function addDefaultData(array $data = []): array
    {
        global $wp_query, $post;

        $data['wp_query'] = $wp_query;
        $data['post'] = &$post;
        $data['current_view'] = $this->current_view;
        $data['request'] = Request::getRootInstance();
        $data['errors'] = Request::getGlobalErrors();

        return $data;
    }
}
