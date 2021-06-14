<?php

namespace Rareloop\Lumberjack\Providers;

use Rareloop\Lumberjack\Config;

class CustomTaxonomyServiceProvider extends ServiceProvider
{
    public function boot(Config $config)
    {
        $taxonomiesToRegister = $config->get('taxonomies.register', []);

        foreach ($taxonomiesToRegister as $taxonomy) {
            // Register
            \add_action('init', function () use ($taxonomy) {
                $taxonomy::register();
            });

            // Polylang
            \add_filter(
                'pll_get_taxonomies',
                function ($taxonomies, $is_settings) use ($taxonomy) {
                    if ($is_settings) {
                        unset($taxonomies[$taxonomy::getTaxonomy()]);
                    } else {
                        $taxonomies[$taxonomy::getTaxonomy()] = $taxonomy::getTaxonomy();
                    }

                    return $taxonomies;
                },
                10,
                2
            );
        }

        $taxonomiesToUnregister = $config->get('taxonomies.unregister', []);
        \add_action('init', function () use ($taxonomiesToUnregister) {
            foreach ($taxonomiesToUnregister as $taxonomy => $postType) {
                \unregister_taxonomy_for_object_type($taxonomy, $postType::getPostType());
            }
        }, 20);
    }
}
