<?php

namespace Rareloop\Lumberjack\Providers;

class YoastSeoServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \add_filter('wpseo_metabox_prio', fn () => 'low');
        \add_filter('wpseo_debug_markers', '__return_false');
    }
}
