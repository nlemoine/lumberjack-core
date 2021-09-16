<?php

namespace Rareloop\Lumberjack\Providers;

use Timber\Timber;

class CustomTaxonomyServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \add_action('init', [$this, 'registerTaxonomies'], 2);
        \add_action('init', [$this, 'unregisterTaxonomies'], 20);
    }

    public function register()
    {
        $this->app->singleton('taxonomy.class_getter', function ($app) {
            $class_map = $this->app->get('taxonomy.class_map');
            return new class($class_map) {
                private $classMap;

                private $defaultClass = Timber\Term::class;

                public function __construct(array $class_map)
                {
                    $this->classMap = $class_map;
                }

                public function getTaxonomyClass($taxonomy)
                {
                    return $this->classMap[$taxonomy] ?? $this->defaultClass;
                }
            };
        });
    }

    public function registerTaxonomies()
    {
        $taxonomies = $this->getConfig('taxonomies.register', []);
        $map = [];
        foreach ($taxonomies as $taxonomy) {
            $taxonomy::register();
            $map[$taxonomy::getTaxonomy()] = $taxonomy;
        }
        $this->app->singleton('taxonomy.class_map', $map);
    }

    public function unregisterTaxonomies()
    {
        $taxonomies = $this->getConfig('taxonomies.unregister', []);
        foreach ($taxonomies as $taxonomy => $postType) {
            \unregister_taxonomy_for_object_type($taxonomy, $postType::getPostType());
        }
    }
}
