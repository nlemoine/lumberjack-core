<?php

namespace Rareloop\Lumberjack\Providers;

use WPSEO_Breadcrumbs;

class BreadcrumbServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('breadcrumbs', function () {
            return $this->getCrumbs();
        });
    }

    public function getCrumbs(): ?array
    {
        if (!\class_exists('WPSEO_Breadcrumbs')) {
            return null;
        }
        $breadcrumbs_instance = WPSEO_Breadcrumbs::get_instance();

        return $breadcrumbs_instance->get_links();
    }

    public function boot()
    {
        \add_filter('timber/context', [$this, 'addBreadcrumbsToContext']);
        \add_action('after_setup_theme', [$this, 'addThemeSupport']);
    }

    public function addThemeSupport()
    {
        \add_theme_support('yoast-seo-breadcrumbs');
    }

    public function addBreadcrumbsToContext(array $context): array
    {
        $context['breadcrumbs'] = $this->app->get('breadcrumbs');
        return $context;
    }
}
