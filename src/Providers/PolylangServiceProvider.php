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
        \add_filter('acf/load_value', [$this, 'loadTranslatedOption'], 10, 3);
    }

    public function loadTranslatedOption($value, $post_id, $field)
    {
        if (\is_admin()) {
            return $value;
        }

        if ($post_id !== 'options') {
            return $value;
        }

        if (!isset($field['translate'])) {
            return $value;
        }

        if (!$field['translate']) {
            return $value;
        }

        $default_language = \pll_default_language();
        $current_language = \pll_current_language();
        if ($current_language === $default_language) {
            return $value;
        }

        // Translate field
        $field['key'] = $field['key'] . '_' . $current_language;
        $field['name'] = $field['name'] . '_' . $current_language;
        $field['_name'] = $field['_name'] . '_' . $current_language;

        // Avoid infinite loop
        \remove_filter('acf/load_value', [$this, 'loadTranslatedOption'], 10);
        $value = \acf_get_value($post_id, $field);
        \add_filter('acf/load_value', [$this, 'loadTranslatedOption'], 10, 3);

        return $value;
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
            $config = $postType::getPostTypeConfig();
            $translate = $config['translate'] ?? true;
            if (!$translate) {
                continue;
            }
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
            $config = $taxonomy::getTaxonomyConfig();
            $translate = $config['translate'] ?? true;
            if (!$translate) {
                continue;
            }
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
