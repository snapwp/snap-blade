<?php

namespace Snap\Blade;

use Snap\Core\Snap;
use Snap\Templating\Templating_Interface;
use eftec\bladeone\BladeOne;

class Strategy implements Templating_Interface
{
	public function __construct()
	{
		$blade = new BladeOne(
			\locate_template(Snap::config('theme.templates_directory')),
			\locate_template(\trailingslashit(Snap::config('theme.cache_directory')) . 'templates'),
			BladeOne::MODE_SLOW
		);

		$blade->setInjectResolver(function ($namespace, $variableName) {
		    return Snap::services()->get($namespace);
		});

		if (is_user_logged_in()) {
			$current_user = wp_get_current_user();
			$blade->setAuth($current_user->data->display_name);
		}

		$blade->setCanFunction(function ($action = null, $subject = null) {
			if ($subject === null) {
				return current_user_can($action);
			}

			return user_can($subject, $action);
		});

		$blade->setAnyFunction(function ($array = []) {
            foreach($array as $permission) {
                if (current_user_can($permission)) {
                	return true;
                }
            }
            
            return false;
        });

		$blade->directive('wphead', function() {
			return '<?php wp_head(); ?>';
		});

		$blade->directive('wpfooter', function () {
			return '<?php wp_footer(); ?>';	
		});

		$blade->directive('loop', function () {
			return '<?php if ($wp_query->have_posts()) : while ($wp_query->have_posts()) : $wp_query->the_post(); ?>';
		});

		$blade->directive('endloop', function () {
			return '<?php endwhile; endif; ?>';
		});

		Snap::services()->addSingleton(BladeOne::class, function() use ($blade) {
			return $blade;
		});

		Snap::services()->alias(BladeOne::class, 'blade');
	}

	public function render($slug, $data = [])
	{
		//echo $this->blade->run('views.'.$slug, $data);
		global $wp_query;
		$data['wp_query'] = $wp_query;

		echo Snap::services()->get('blade')->run($slug, $data);
	}

	public function partial($slug, $data = [])
	{
		//dump($slug, $data);
		echo Snap::services()->get('blade')->runChild('partials.'.$slug, $data);
	}

}