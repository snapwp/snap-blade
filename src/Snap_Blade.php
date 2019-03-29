<?php

namespace Snap\Blade;

use eftec\bladeone\BladeOne;
use Snap\Exceptions\Templating_Exception;
use Snap\Templating\View;

/**
 * Extends BladeOne.
 */
class Snap_Blade extends BladeOne
{
    /**
     * Overwrite the default error handler to throw a Templating_exception.
     *
     * @since  1.0.0
     *
     * @throws Templating_Exception
     * 
     * @param string $id     Title of the error.
     * @param string $text   Message of the error.
     * @param bool   $critic If true then the compilation is ended, otherwise it continues.
     */
	public function showError($id, $text, $critic = false)
    {
        \ob_get_clean();
        throw new Templating_Exception($text);
    }

    /**
     * Ensures any additional data provided by View::when() is passed to BladeOne.
     *
     * @since  1.0.0
     *
     * @param string $partial The template name to render.
     * @param array  $data    The data to pass to BladeOne.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function runChild($partial, $data = array())
    {
        return parent::runChild(
            $partial, 
            \array_merge(
                $this->variables,
                // Get default data for the current template.
                View::get_additional_data($partial, $data),
                // Ensure data passed into parent view is passed to this child.
                $data
            )
        );
    }

    /**
     * Run the blade engine. Is only called when rendering a view.
     *
     * @since  1.0.0
     *
     * @param string $view The template name to render.
     * @param array  $data The data to pass to BladeOne.
     * @return string
     *
     * @throws \Exception
     */
    public function run($view, $data = array())
    {
        return parent::run(
            $view,
            \array_merge(View::get_additional_data($view, $data), $data)
        );
    }

    /**
     * Ensure the real dd() function is used if present.
     *
     * @param string $expression The expression to pass to dd().
     * @return string
     */
    protected function compileDd($expression)
    {
        if (\function_exists('dd')) {
            return $this->phpTag . " dd$expression; ?>";
        }

        return $this->phpTag." echo '<pre>'; var_dump$expression; echo '</pre>'; die;?>";
    }

    /**
     * Ensure the real dump() function is used if present.
     *
     * @param string $expression The expression to pass to dump().
     * @return string
     */
    protected function compileDump($expression)
    {
        if (\function_exists('dump')) {
            return $this->phpTag . " dump$expression; ?>";
        }

        return $this->phpTag." echo '<pre>'; var_dump$expression; echo '</pre>';?>";
    }
}