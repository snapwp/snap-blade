<?php

namespace Snap\Blade;

use Snap\Core\Snap;
use Snap\Services\Provider;
use Snap\Templating\Templating_Interface;


/**
 * Snap Debug service provider.
 */
class Blade_Provider extends Provider
{
    /**
     * Register the Service.
     *
     * @since  1.0.0
     */
    public function register()
    {
    	Snap::services()->add(
            Strategy::class,
            function ($hodl) {
                return new Strategy;
            }
        );    	


        Snap::services()->bind(Strategy::class, Templating_Interface::class);
    }

}
