<?php

namespace Rareloop\Lumberjack\Providers;

class CustomTaxonomyServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \add_action('init', [$this, 'registerTaxonomies'], 2);
        \add_action('init', [$this, 'unregisterTaxonomies'], 20);
    }

    public function registerTaxonomies()
    {
        $taxonomies = $this->getConfig('taxonomies.register', []);
        $map = [];
        foreach ($taxonomies as $taxonomy) {
            $taxonomy::register();
            $map[$taxonomy::getTaxonomy()] = $taxonomy;
        }
        $this->app->singleton('taxonomy.class_map', $map); // TODO: remove this
    }

    public function unregisterTaxonomies()
    {
        $taxonomies = $this->getConfig('taxonomies.unregister', []);
        foreach ($taxonomies as $taxonomy => $postType) {
            \unregister_taxonomy_for_object_type($taxonomy, $postType::getPostType());
        }
    }
}
