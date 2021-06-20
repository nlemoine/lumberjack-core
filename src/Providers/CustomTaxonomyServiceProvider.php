<?php

namespace Rareloop\Lumberjack\Providers;

class CustomTaxonomyServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \add_action('init', [$this, 'registerTaxonomies']);
        \add_action('init', [$this, 'unregisterTaxonomies'], 20);
    }

    public function registerTaxonomies()
    {
        $taxonomies = $this->getConfig('taxonomies.register', []);
        foreach ($taxonomies as $taxonomy) {
            $taxonomy::register();
        }
    }

    public function unregisterTaxonomies()
    {
        $taxonomies = $this->getConfig('taxonomies.unregister', []);
        foreach ($taxonomies as $taxonomy => $postType) {
            \unregister_taxonomy_for_object_type($taxonomy, $postType::getPostType());
        }
    }
}
