<?php

namespace Snap\Blade;

use eftec\bladeone\BladeOne;
use Snap\Exceptions\Templating_Exception;

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
     * @param string $id     Title of the error.
     * @param string $text   Message of the error.
     * @param bool   $critic If true then the compilation is ended, otherwise it continues.
     */
	public function showError($id, $text, $critic = false)
    {
        \ob_get_clean();
        throw new Templating_Exception($text);
    }
}