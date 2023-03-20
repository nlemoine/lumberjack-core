<?php

namespace Rareloop\Lumberjack;

use Timber\Timber as TimberCore;

class Timber extends TimberCore
{
    public function __construct()
    {
        if (!\defined('ABSPATH')) {
            return;
        }

        if (\class_exists('WP') && !\defined('TIMBER_LOADED')) {
            $this->init_constants();
            self::init();
        }
    }

    public static function compile($filenames, $data = [], $expires = false, $cache_mode = null, $via_render = false)
    {
        if (!\defined('TIMBER_LOADED')) {
            self::init();
        }
        $loader = new Loader();
        $twig = $loader->get_twig();

        return $twig->resolveTemplate($filenames)->render($data);
    }

    public static function context(array $extra = []): array
    {
        $context = self::context_global();

        if (\is_singular()) {
            // NOTE: this also handles the is_front_page() case.
            $context['post'] = self::get_post();
        } elseif (\is_home()) {
            // show_on_front = page
            $context['post'] = self::get_post();
            $context['posts'] = self::get_posts();
        } elseif (\is_category() || \is_tag() || \is_tax()) {
            $context['term'] = self::get_term();
            $context['posts'] = self::get_posts();
        } elseif (\is_search()) {
            $context['posts'] = self::get_posts();
            $context['search_query'] = \get_search_query();
        } elseif (\is_author()) {
            $context['author'] = self::get_user(\get_query_var('author'));
            $context['posts'] = self::get_posts();
        } elseif (\is_archive()) {
            $context['posts'] = self::get_posts();
        }

        $context = \apply_filters('timber/context', $context);

        return \array_merge($context, $extra);
    }
}
