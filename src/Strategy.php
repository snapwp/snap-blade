<?php

namespace Snap\Blade;

use Snap\Core\Snap;
use Snap\Templating\Templating_Interface;
use eftec\bladeone\BladeOne;

/**
 * The Blade templating strategy.
 */
class Strategy implements Templating_Interface
{
    /**
     * The current view name being displayed.
     *
     * @since  1.0.0
     * @var string|null
     */
    protected $current_view = null;

    /**
     * Renders a view.
     *
     * @since  1.0.0
     *
     * @param  string $slug The slug for the generic template.
     * @param  array  $data Optional. Additional data to pass to a partial. Available in the partial as $data.
     */
    public function render($slug, $data = [])
    {
        $this->current_view = $this->get_template_name($slug);

        $data = $this->add_default_data($data);

        echo Snap::services()->get('blade')->run($this->current_view, $data);
    }

    /**
     * Fetch and display a template partial.
     *
     * @since  1.0.0
     *
     * @param  string $slug The slug for the generic template.
     * @param  array  $data Optional. Additional data to pass to a partial. Available in the partial as $data.
     */
    public function partial($slug, $data = [])
    {
        $data = $this->add_default_data($data);

        echo Snap::services()->get('blade')->run('partials.' . $this->bladeify($slug), $data);
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
        $slug = \str_replace([ Snap::config('theme.templates_directory') . '/', '.php' ], '', $slug);

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
     */
    private function add_default_data($data = [])
    {
        global $wp_query, $post;
        
        $data['wp_query'] = $wp_query;
        $data['post'] = &$post;
        $data['current_view'] = $this->current_view;

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
