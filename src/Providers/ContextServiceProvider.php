<?php

namespace Rareloop\Lumberjack\Providers;

use Rareloop\Lumberjack\Context;
use Symfony\Component\HttpFoundation\Request;

class ContextServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \add_filter('timber/context', [$this, 'removeUnneededContext'], 1);
        \add_filter('timber/context', function ($context) {
            $context['app'] = $this->app->get('context');
            $context['option'] = $context['app']->getOption();
            $context['menu'] = $context['app']->getMenu();
            return $context;
        });
        // Temp, time that Timber fixes collections/post global issues
        \add_filter('timber/context', [$this, 'addDebugContext'], 1);
        \add_filter('timber/context', [$this, 'addLanguagesContext'], 1);
        \add_filter('timber/context', [$this, 'addQueryContext'], 1);
        \add_filter('timber/context', [$this, 'addDataContext'], 20);
        \add_filter('timber/context', [$this, 'renameCollections'], 20);
    }

    public function register()
    {
        $this->app->singleton('context', function () {
            return new Context($this->app);
        });
    }

    /**
     * Undocumented function
     */
    public function renameCollections(array $context): array
    {
        $post_type = $this->app->get('context')->getPostType();
        $post_type = \str_replace('-', '_', $post_type);

        if (\is_singular() && isset($context['post']) && $post_type !== 'post') {
            $context[$post_type] = $context['post'];
            unset($context['post']);
        } elseif (
            (\is_home() || \is_post_type_archive() || \is_tax() || \is_category() || \is_tag())
            && $post_type !== 'post'
            && isset($context['posts'])
        ) {
            $context[$post_type . 's'] = $context['posts'];
            unset($context['posts']);
        }

        if ((\is_tax() || \is_category() || \is_tag()) && isset($context['term'])) {
            $taxonomy = \str_replace('-', '_', \get_queried_object()->taxonomy);
            $context[$taxonomy] = $context['term'];
            unset($context['term']);
        }

        if (\is_home() && isset($context['post'])) {
            $context['page'] = $context['post'];
            unset($context['post']);
        }

        return $context;
    }

    /**
     * Undocumented function
     */
    public function addDebugContext(array $context): array
    {
        $context['debug'] = WP_DEBUG;
        $context['env'] = \defined('WP_ENV') ? WP_ENV : false;
        return $context;
    }

    /**
     * Undocumented function
     */
    public function addLanguagesContext(array $context): array
    {
        $context['current_lang'] = $this->app->get('locale.short');
        $context['locale'] = $this->app->get('locale');
        return $context;
    }

    /**
     * Undocumented function
     */
    public function addDataContext(array $context): array
    {
        $context['title'] = $this->app->get('context')->getTitle();
        $context['request'] = Request::createFromGlobals();
        return $context;
    }

    /**
     * Undocumented function
     */
    public function addQueryContext(array $context): array
    {
        $context['query'] = $GLOBALS['wp_query'];
        return $context;
    }

    /**
     * Undocumented function
     */
    public function removeUnneededContext(array $context): array
    {
        unset($context['http_host'], $context['wp_title'], $context['body_class'], $context['theme'], $context['request']);
        return $context;
    }
}
