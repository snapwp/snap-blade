<?php

namespace Snap\Blade;

use eftec\bladeone\BladeOne;
use Snap\Services\Config;
use Snap\Services\Container;
use Snap\Services\Service_Provider;
use Snap\Templating\Standard\Partial;
use Snap\Templating\Standard\Standard_Strategy;
use Snap\Templating\Templating_Interface;
use Snap\Utils\Theme_Utils;
use Snap\Utils\User_Utils;

/**
 * Snap Debug service provider.
 */
class Blade_Service_Provider extends Service_Provider
{
    /**
     * Register the Service.
     *
     * @since  1.0.0
     *
     */
    public function register()
    {
        $this->add_config_location(\realpath(__DIR__ . '/../config'));

        $this->add_blade();

        Container::add(
            Blade_Strategy::class,
            function (\Snap\Core\Container $container) {
                return $container->resolve(Blade_Strategy::class);
            }
        );

        Container::remove(Standard_Strategy::class);
        Container::remove(Partial::class);

        // Bind blade strategy as the current Templating engine.
        Container::bind(Blade_Strategy::class, Templating_Interface::class);

        $this->publishes_config(\realpath(__DIR__ . '/../config'));

        $this->publishes_directory(
            \realpath(__DIR__ . '/../templates'),
            Config::get('theme.templates_directory')
        );
    }

    /**
     * Creates the Snap_blade instance, and adds to service container.
     *
     * @since  1.0.0
     */
    public function add_blade()
    {
        $blade = new Snap_Blade(
            Theme_Utils::get_active_theme_path(Config::get('theme.templates_directory')),
            Theme_Utils::get_active_theme_path(\trailingslashit(Config::get('theme.cache_directory')) . 'templates'),
            Config::get('blade.development_mode') ? BladeOne::MODE_SLOW : BladeOne::MODE_FAST
        );

        if (Config::get('blade.file_extension') !==  $blade->getFileExtension()) {
            $blade->setFileExtension(Config::get('blade.file_extension'));
        }

        // Set the @inject directive to resolve from the service container.
        $blade->setInjectResolver(
            function ($namespace) {
                return Container::get($namespace);
            }
        );

        $this->set_auth_callbacks($blade);

        $this->add_directives($blade);
        $this->add_wp_directives($blade);

        Container::add_instance($blade);

        Container::alias(Snap_Blade::class, 'blade');
    }

    /**
     * Sets the authentication and can/cannot functionality.
     *
     * @since  1.0.0
     *
     * @param BladeOne $blade The BladeOne service.
     */
    private function set_auth_callbacks($blade)
    {
        // Set the current user.
        if (\is_user_logged_in()) {
            $current_user = \wp_get_current_user();
            $blade->setAuth($current_user->data->display_name, User_Utils::get_user_role()->name);
        }

        // Set the @can directive.
        $blade->setCanFunction(
            function ($action = null, $subject = null) {
                if ($subject === null) {
                    return \current_user_can($action);
                }

                return \user_can($subject, $action);
            }
        );

        // Set the @canany directive.
        $blade->setAnyFunction(
            function ($array = []) {
                foreach ($array as $permission) {
                    if (\current_user_can($permission)) {
                        return true;
                    }
                }
            
                return false;
            }
        );
    }

    /**
     * Add custom directives to blade.
     *
     * @since  1.0.0
     *
     * @param BladeOne $blade The BladeOne service.
     */
    private function add_directives($blade)
    {
        $blade->directive(
            'simplemenu',
            function ($expression) {
                \preg_match('/([^\s]*)\s?as\s?(.*)/', $expression, $matches);
                
                $iteratee = \trim($matches[1]);
                
                $iteration = \trim($matches[2]);
                
                $init_loop = "\$__currentLoopData = \Snap\Utils\Menu_Utils::get_nav_menu($iteratee); 
                    \$this->addLoop(\$__currentLoopData);";
               
                $iterate_loop = '$this->incrementLoopIndices(); 
                    $loop = $this->getFirstLoop();';

                return "<?php {$init_loop} foreach(\$__currentLoopData as {$iteration}): {$iterate_loop} ?>";
            }
        );

        $blade->directive(
            'endsimplemenu',
            function () {
                return '<?php endforeach; $this->popLoop(); $loop = $this->getFirstLoop(); ?>';
            }
        );

        $blade->directive(
            'partial',
            function ($input) {
                $input = $this->trim_input($input);
                $input = \str_replace('partials.\'', 'partials.', '\'partials.' . $input);
                return '<?php echo $this->runChild('.$input.'); ?>';
            }
        );

        $blade->directive(
            'posttypepartial',
            function () {
                return '<?php global $post; echo $this->runChild(\'partials.post-type.\' . \get_post_type(), [\'post\' => $post]); ?>';
            }
        );

        $blade->directive(
            'paginate',
            function ($input = []) {
                $input = $input ?? '[]';

                return '
			<?php
			$pagination = \Snap\Services\Container::resolve(
	            \Snap\Templating\Pagination::class,
	            [
	                \'args\' => ' . $this->trim_input($input) .',
	            ]
	        );

	        echo $pagination->get();
	        ?>';
            }
        );

        $blade->directive(
            'loop',
            function ($input) {
                $input = $this->trim_input($input);

                $init_loop = '$__loop_query = $wp_query;';

                if (! empty($input)) {
                    $init_loop = '$__loop_query = ' . $input . ';';
                }

                $init_loop .= '$__currentLoopData = $__loop_query->posts; 
                    $this->addLoop($__currentLoopData); 
                    global $post;';

                $iterate_loop = '$this->incrementLoopIndices(); $loop = $this->getFirstLoop();';

                return "<?php {$init_loop} while (\$__loop_query->have_posts()): 
                    \$__loop_query->the_post(); {$iterate_loop} ?>";
            }
        );

        $blade->directive(
            'endloop',
            function () {
                return '<?php endwhile;  ?>';
            }
        );
    }

    /**
     * Add custom directives for WordPress functions to blade.
     *
     * @since  1.0.0
     *
     * @param BladeOne $blade The BladeOne service.
     */
    private function add_wp_directives($blade)
    {
        $blade->directive(
            'wphead',
            function () {
                return '<?php wp_head(); ?>';
            }
        );

        $blade->directive(
            'wpfooter',
            function () {
                return '<?php wp_footer(); ?>';
            }
        );

        $blade->directive(
            'sidebar',
            function ($input) {
                return "<?php dynamic_sidebar({$this->trim_input($input)}); ?>";
            }
        );

        $blade->directive(
            'action',
            function ($input) {
                return "<?php do_action({$this->trim_input($input)}); ?>";
            }
        );

        $blade->directive(
            'thecontent',
            function () {
                return "<?php the_content(); ?>";
            }
        );

        $blade->directive(
            'theexcerpt',
            function () {
                return "<?php the_excerpt(); ?>";
            }
        );

        $blade->directive(
            'navmenu',
            function ($input) {
                return "<?php wp_nav_menu({$this->trim_input($input)}); ?>";
            }
        );

        $blade->directive(
            'searchform',
            function () {
                return "<?php get_search_form(); ?>";
            }
        );

        $blade->directive(
            'setpostdata',
            function ($input) {
                return '<?php setup_postdata($GLOBALS[\'post\'] =& '. $this->trim_input($input) .'); ?>';
            }
        );

        $blade->directive(
            'resetpostdata',
            function () {
                return '<?php wp_reset_postdata(); ?>';
            }
        );
    }

    /**
     * Remove surrounding brackets from BladeOne inputs.
     *
     * @since 1.0.0
     *
     * @param $input
     * @return string
     */
    private function trim_input($input)
    {
        return \trim($input, '()');
    }
}
