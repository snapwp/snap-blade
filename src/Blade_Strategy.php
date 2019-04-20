<?php

namespace Snap\Blade;

use Snap\Http\Validation\Validation;
use Snap\Services\Config;
use Snap\Services\Request;
use Snap\Templating\Templating_Interface;

/**
 * The Blade templating strategy.
 */
class Blade_Strategy implements Templating_Interface
{
    /**
     * The current view name being displayed.
     *
     * @since  1.0.0
     * @var string|null
     */
    protected $current_view = null;

    /**
     * Snap_Blade instance.
     *
     * @since 1.0.0
     * @var Snap_Blade
     */
    private $blade;

    public function __construct(Snap_Blade $blade)
    {
        $this->blade = $blade;
    }

    /**
     * Renders a view.
     *
     * @since  1.0.0
     *
     * @param  string $slug The slug for the generic template.
     * @param  array  $data Optional. Additional data to pass to a partial. Available in the partial as $data.
     * @throws \Exception
     */
    public function render($slug, $data = [])
    {
        $this->current_view = $this->get_template_name($slug);

        $data = $this->add_default_data($data);

        echo $this->blade->run($this->current_view, $data);

        // Now a view has been rendered, reset the current_view context.
        $this->current_view = null;
    }

    /**
     * Fetch and display a template partial.
     *
     * @since  1.0.0
     *
     * @param  string $slug The slug for the generic template.
     * @param  array  $data Optional. Additional data to pass to a partial. Available in the partial as $data.
     * @throws \Exception
     */
    public function partial($slug, $data = [])
    {
        $data = $this->add_default_data($data);

        // Check if this is being run outside of a view context.
        if ($this->current_view === null) {
            echo $this->blade->run('partials.' . $this->bladeify($slug), $data);
            return;
        }

        echo $this->blade->runChild('partials.' . $this->bladeify($slug), $data);
    }

    /**
     * Generate the template file name from the slug.
     *
     * @since 1.0.0
     *
     * @param  string $slug The slug for the generic template.
     * @return string
     */
    public function get_template_name($slug)
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

        return $this->bladeify($slug);
    }

    /**
     * Returns the current view template name.
     *
     * @since 1.0.0
     *
     * @return string|null Returns null if called before a view has been dispatched.
     */
    public function get_current_view()
    {
        return $this->current_view;
    }

    /**
     * Add default data to template.
     *
     * @since  1.0.0
     *
     * @param array $data Data array.
     * @return array
     */
    private function add_default_data($data = [])
    {
        global $wp_query, $post;
        
        $data['wp_query'] = $wp_query;
        $data['post'] = &$post;
        $data['current_view'] = $this->current_view;
        $data['request'] = Request::get_root_instance();
        $data['errors'] = Validation::$errors;

        return $data;
    }

    /**
     * Replace slashes with periods.
     *
     * @since  1.0.0
     *
     * @param  string $slug Template path to bladeify.
     * @return string
     */
    private function bladeify($slug)
    {
        return \str_replace(['/', '\\'], '.', $slug);
    }
}
