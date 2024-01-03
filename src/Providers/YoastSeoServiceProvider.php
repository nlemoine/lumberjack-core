<?php

namespace Rareloop\Lumberjack\Providers;

use Rareloop\Lumberjack\Router\Symfony\CurrentRoute;
use Yoast\WP\SEO\Context\Meta_Tags_Context;
use Yoast\WP\SEO\Generators\Schema;
use Yoast\WP\SEO\Presentations\Indexable_Presentation;

class YoastSeoServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \add_filter('wpseo_metabox_prio', fn () => 'low');
        \add_filter('wpseo_debug_markers', '__return_false');
        \add_action('app.route_matched', [$this, 'onRouteMatched']);
    }

    /**
     * Hooks to execute when a route is matched
     */
    public function onRouteMatched(CurrentRoute $currentRoute)
    {
        // Yoast SEO
        if (!\function_exists('YoastSEO')) {
            return;
        }
        // Breadcrumbs
        \add_filter('wpseo_breadcrumb_links', function ($crumbs) use ($currentRoute) {
            $last = \array_key_last($crumbs);
            $crumbs[$last]['url'] = $currentRoute->canonical;
            $crumbs[$last]['text'] = $currentRoute->title;
            return $crumbs;
        });

        $model = new class($currentRoute) {
            private $currentRoute;

            public function __construct($currentRoute)
            {
                $this->currentRoute = $currentRoute;
            }

            public function __get($property)
            {
                $value = null;
                switch ($property) {
                    // Handled by https://github.com/Yoast/wordpress-seo/blob/faa422715fc03e4ef07367f59e6196e98e3abc9b/src/presenters/title-presenter.php#L39-L42
                    case 'title':
                        $property = null;
                        break;
                    case 'permalink':
                        $property = 'canonical';
                        break;
                    case 'object_type':
                        $value = 'post';
                        break;
                    case 'open_graph_type':
                    case 'object_sub_type':
                        $value = 'page';
                        break;
                    case 'name':
                    case 'open_graph_title':
                    case 'twitter_title':
                        $property = 'title';
                        break;
                    case 'open_graph_description':
                        $property = 'description';
                        break;
                }
                return $value ?? $this->currentRoute->{$property};
            }
        };

        // Presenters
        \add_filter('wpseo_frontend_presentation', function (Indexable_Presentation $presentation, $context) use ($model) {
            $presentation->model = $model;
            return $presentation;
        }, 10, 2);

        // JSON+LD
        \add_filter('wpseo_schema_graph_pieces', function (array $pieces, Meta_Tags_Context $context) {
            $pieces[] = new class($context) extends Schema\WebPage {
                public function is_needed()
                {
                    return true;
                }

                public function generate()
                {
                    $data = parent::generate();
                    // Fix properties
                    $data['@id'] = $this->context->canonical;
                    $data['name'] = $this->context->presentation->model->name;
                    return $data;
                }
            };
            $pieces[] = new class($context) extends Schema\Breadcrumb {
                public function is_needed()
                {
                    return true;
                }
            };
            return $pieces;
        }, 10, 2);
    }
}
