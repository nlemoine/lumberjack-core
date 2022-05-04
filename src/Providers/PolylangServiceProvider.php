<?php

namespace Rareloop\Lumberjack\Providers;

use PLL_Base;

class PolylangServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \add_filter('timber/context', [$this, 'addLanguagesContext'], 1);

        if (!\function_exists('PLL')) {
            return;
        }

        \add_filter('pll_get_post_types', [$this, 'registerPostTypes'], 10, 2);
        \add_filter('pll_get_taxonomies', [$this, 'registerTaxonomies'], 10, 2);
    }

    /**
     * Register translatables post types
     *
     * @param boolean $is_settings
     */
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

    /**
     * Register translatables taxonomies
     *
     * @param boolean $is_settings
     */
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

    /**
     * Undocumented function
     */
    public function addLanguagesContext(array $context): array
    {
        $context['languages'] = $this->app->has('polylang.languages') ? $this->app->get('polylang.languages') : [];
        return $context;
    }

    public function register()
    {
        if (!\function_exists('PLL')) {
            return;
        }

        $this->app->singleton('pll', function (): ?PLL_Base {
            if (\function_exists('PLL')) {
                return \PLL();
            }

            return null;
        });

        $this->app->singleton('polylang.languages.slugs', function () {
            $pll = $this->app->get('pll');

            return $pll ? $pll->model->get_languages_list([
                'fields' => 'slug',
            ]) : [$this->app->get('locale.short')];
        });

        $this->app->singleton('polylang.languages', function () {
            return \pll_the_languages([
                'raw'          => 1,
                'hide_current' => 0,
            ]);
        });
    }
}
