<?php

namespace Rareloop\Lumberjack\Providers;

use PLL_Base;
use Rareloop\Lumberjack\Helpers;

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
        \add_action('acf/init', [$this, 'init']);
        \add_filter('pll_translation_url', [$this, 'translateUrl'], 10, 2);
    }

    /**
     * Translate custom route url
     */
    public function translateUrl(?string $url, string $lang): ?string
    {
        if (!empty($url)) {
            return $url;
        }

        if (!Helpers::app()->has('router.current_route')) {
            return $url;
        }

        $currentRoute = Helpers::app()->get('router.current_route');
        return $currentRoute->getCanonical([
            '_locale' => $lang,
        ]);
    }

    public function init()
    {
        // Disable ACFE Multilingual
        \acf_update_setting('acfe/modules/multilang', false);
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
        $registeredTaxonomies = $this->getConfig('taxonomies.register', []);

        foreach ($registeredTaxonomies as $taxonomy) {
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

        $this->app->singleton('polylang', function (): ?PLL_Base {
            if (\function_exists('PLL')) {
                return \PLL();
            }

            return null;
        });

        $this->app->singleton('polylang.current_language', function () {
            return $this->app->get('polylang')->curlang;
        });
        $this->app->singleton('polylang.locales', function () {
            return \array_column($this->app->get('polylang.languages'), 'locale');
        });
        $this->app->singleton('polylang.w3c', function () {
            return \array_column($this->app->get('polylang.languages'), 'w3c');
        });
        $this->app->singleton('polylang.slugs', function () {
            return \array_column($this->app->get('polylang.languages'), 'slug');
        });
        $this->app->singleton('polylang.url_prefix', function () {
            return $this->getConfig('polylang.url_prefix', 'slug');
        });
        $this->app->singleton('polylang.languages', function () {
            return \pll_the_languages([
                'raw'          => 1,
                'hide_current' => 0,
            ]);
        });
    }
}
