<?php

namespace Rareloop\Lumberjack\Providers;

use WP;

class DisableSearchServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Disable search
        \add_action('admin_bar_menu', [$this, 'removeSearchFromAdminBar']);
        if (!\is_admin()) {
            \add_action('parse_request', [$this, 'disbaleSearchRequest']);
        }
    }

    public function removeSearchFromAdminBar($wp_admin_bar)
    {
        $wp_admin_bar->remove_menu('search');
    }

    public function disbaleSearchRequest(WP $request)
    {
        unset($request->query_vars['s'], $request->query_vars['search']);
    }
}
