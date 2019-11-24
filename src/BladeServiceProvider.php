<?php

namespace Snap\Blade;

use eftec\bladeone\BladeOne;
use Snap\Services\Config;
use Snap\Services\Container;
use Snap\Services\ServiceProvider;
use Snap\Templating\Standard\StandardStrategy;
use Snap\Templating\TemplatingInterface;
use Snap\Utils\Theme;
use Snap\Utils\User;


/**
 * Snap Debug service provider.
 */
class BladeServiceProvider extends ServiceProvider
{
    /**
     * Register the Service.
     */
    public function register()
    {
        $this->addConfigLocation(\realpath(__DIR__ . '/../config'));

        $this->addBlade();

        Container::add(
            BladeStrategy::class,
            function (\Hodl\Container $container) {
                return $container->resolve(BladeStrategy::class);
            }
        );

        Container::remove(StandardStrategy::class);

        // Bind blade strategy as the current Templating engine.
        Container::bind(BladeStrategy::class, TemplatingInterface::class);

        $this->publishesConfig(\realpath(__DIR__ . '/../config'));

        $this->publishesDirectory(
            \realpath(__DIR__ . '/../templates'),
            Config::get('theme.templates_directory')
        );
    }

    /**
     * Creates the Snap_blade instance, and adds to service container.
     *
     * @since  1.0.0
     */
    public function addBlade()
    {
        $blade = new SnapBlade(
            Theme::getActiveThemePath(Config::get('theme.templates_directory')),
            Theme::getActiveThemePath(\trailingslashit(Config::get('theme.cache_directory')) . 'templates'),
            Config::get('blade.development_mode') ? BladeOne::MODE_SLOW : BladeOne::MODE_AUTO
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

        $blade->setBaseUrl(\get_stylesheet_directory_uri());

        $this->setAuthCallbacks($blade);

        $this->addDirectives($blade);
        $this->addWpDirectives($blade);

        Container::addInstance($blade);

        Container::alias(SnapBlade::class, 'blade');
    }

    /**
     * Sets the authentication and can/cannot functionality.
     *
     * @param BladeOne $blade The BladeOne service.
     */
    private function setAuthCallbacks($blade)
    {
        // Set the current user.
        if (\is_user_logged_in()) {
            $current_user = \wp_get_current_user();
            $blade->setAuth($current_user->data->display_name, User::getUserRole()->name);
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
     * @param BladeOne $blade The BladeOne service.
     */
    private function addDirectives($blade)
    {
        $blade->directive(
            'simplemenu',
            function ($expression) {
                \preg_match('/([^\s]*)\s?as\s?(.*)/', $expression, $matches);
                
                $iteratee = \trim($matches[1]);
                
                $iteration = \trim($matches[2]);
                
                $init_loop = "\$__currentLoopData = \Snap\Utils\Menu::getNavMenu($iteratee); 
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
                $input = $this->trimInput($input);
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
	                \'args\' => ' . $this->trimInput($input) .',
	            ]
	        );

	        echo $pagination->get();
	        ?>';
            }
        );

        $blade->directive(
            'loop',
            function ($input) {
                $input = $this->trimInput($input);

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
                return '<?php wp_reset_postdata(); endwhile;  ?>';
            }
        );
    }

    /**
     * Add custom directives for WordPress functions to blade.
     *
     * @param BladeOne $blade The BladeOne service.
     */
    private function addWpDirectives($blade)
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
                return "<?php dynamic_sidebar({$this->trimInput($input)}); ?>";
            }
        );

        $blade->directive(
            'action',
            function ($input) {
                return "<?php do_action({$this->trimInput($input)}); ?>";
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
                return "<?php wp_nav_menu({$this->trimInput($input)}); ?>";
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
                return '<?php setup_postdata($GLOBALS[\'post\'] =& '. $this->trimInput($input) .'); ?>';
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
     * @param $input
     * @return string
     */
    private function trimInput($input)
    {
        return \trim($input, '()');
    }
}
