<?php

namespace Rareloop\Lumberjack;

use Timber\Integrations;
use Timber\Timber as TimberCore;
use Timber\Twig;

class Timber extends TimberCore
{
    public function __construct()
    {
        if (!\defined('ABSPATH')) {
            return;
        }

        if (\class_exists('WP') && !\defined('TIMBER_LOADED')) {
            $this->test_compatibility();
            $this->init_constants();
            self::init();
        }
    }

    public static function context(array $extra = []): array
    {
        $context = self::context_global();

		if ( is_singular() ) {
			// NOTE: this also handles the is_front_page() case.
			$context['post'] = Timber::get_post();
		} elseif ( is_home() ) {
			// show_on_front = page
			$context['post']  = Timber::get_post();
			$context['posts'] = Timber::get_posts();
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$context['term']  = Timber::get_term();
			$context['posts'] = Timber::get_posts();
		} elseif ( is_search() ) {
			$context['posts']        = Timber::get_posts();
			$context['search_query'] = get_search_query();
		} elseif ( is_author() ) {
			$context['author'] = Timber::get_user(get_query_var('author'));
			$context['posts']  = Timber::get_posts();
		} elseif ( is_archive() ) {
			$context['posts'] = Timber::get_posts();
		}

        $context = apply_filters( 'timber/context', $context );

        return array_merge( $context, $extra );
    }

    protected function test_compatibility()
    {
        if (\is_admin() || $_SERVER['PHP_SELF'] === '/wp-login.php') {
            return;
        }
    }

    protected static function init()
    {
        if (\class_exists('\WP') && !\defined('TIMBER_LOADED')) {
            Twig::init();
            new Integrations();
            \define('TIMBER_LOADED', true);
        }
    }
}
