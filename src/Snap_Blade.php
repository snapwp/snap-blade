<?php

namespace Snap\Blade;

use eftec\bladeone\BladeOne;

class Snap_Blade extends BladeOne
{
	
	protected function compileLoop($expression)
    {
    	return $this->phpTag . 'if ($wp_query->have_posts()) :
            while ($wp_query->have_posts()) :
                $wp_query->the_post(); 
                ?>' .
                $expression;
    }

    protected function compileEndloop($expression)
    {
        return $this->phpTag.'endwhile; endif; ?>';
    }
}