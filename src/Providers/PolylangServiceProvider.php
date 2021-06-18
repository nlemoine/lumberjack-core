<?php

namespace Rareloop\Lumberjack\Providers;

class PolylangServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \add_filter('pll_get_post_types', [$this, 'registerPostTypes'], 10, 2);
        \add_filter('pll_get_taxonomies', [$this, 'registerTaxonomies'], 10, 2);
    }

    public function registerPostTypes(array $post_types, bool $is_settings): array
    {
        $postTypes = $this->getConfig('posttypes.register', []);

        foreach ($postTypes as $postType) {
            if ($is_settings) {
                unset($post_types[$postType::getPostType()]);
            } else {
                $post_types[$postType::getPostType()] = $postType::getPostType();
            }
        }

        return $post_types;
    }

    public function registerTaxonomies(array $taxonomies, bool $is_settings): array
    {
        $taxonomies = $this->getConfig('taxonomies.register', []);

        foreach ($taxonomies as $taxonomy) {
            if ($is_settings) {
                unset($taxonomies[$taxonomy::getTaxonomy()]);
            } else {
                $taxonomies[$taxonomy::getTaxonomy()] = $taxonomy::getTaxonomy();
            }
        }

        return $taxonomies;
    }
}
