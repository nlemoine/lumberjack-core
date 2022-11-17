<?php

namespace Rareloop\Lumberjack;

use Symfony\Component\HttpFoundation\Request;

class Context extends \ArrayObject
{
    private $app;

    private $option;

    private $menu;

    private $acfOption;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->option = new class() {
            public function __call($name, $args)
            {
                return $this->__get($name);
            }

            public function get($name)
            {
                return $this->__get($name);
            }

            public function __get(string $name)
            {
                return \once(function () use ($name) {
                    return \get_option($name);
                });
            }
        };
        if (\function_exists('get_field')) {
            $this->acfOption = new class() {
                public function __call($name, $args)
                {
                    return $this->__get($name);
                }

                public function __get(string $name)
                {
                    return \once(function () use ($name) {
                        return \get_field($name, 'option');
                    });
                }
            };
        }
        $this->menu = new class() {
            public function __call($location, $args)
            {
                return $this->__get($location);
            }

            public function __get(string $location)
            {
                $location = \str_replace('_', '-', $location);
                return \once(function () use ($location) {
                    return Timber::get_menu($location);
                });
            }
        };
    }

    public function getArrayCopy(): array
    {
        return [
            'option' => $this->option,
            'menu'   => $this->menu,
        ];
    }

    public function getMenu()
    {
        return $this->menu;
    }

    public function getOption()
    {
        return $this->option;
    }

    public function getAcfOption()
    {
        return $this->acfOption;
    }

    public function getRequest()
    {
        return Request::createFromGlobals();
    }

    public function getEnv()
    {
        return \defined('WP_ENV') ? WP_ENV : false;
    }

    /**
     * Get post type by all means available
     */
    public function getPostType(): ?string
    {
        $post_type = \get_post_type();
        if (!$post_type) {
            if (isset($GLOBALS['wp_query']->is_page_for_custom_post_type)) {
                $post_type = $GLOBALS['wp_query']->is_page_for_custom_post_type;
            } elseif (\get_query_var('post_type')) {
                $post_type = \get_query_var('post_type');
            }
        }

        if (!$post_type && \is_tax()) {
            $taxonomy = \get_taxonomy(\get_queried_object()->taxonomy);
            $post_type = $taxonomy->object_type[0] ?? null;
        }

        return $post_type;
    }

    public function getQuery()
    {
        return $GLOBALS['wp_query'];
    }

    /**
     * Returns some or all the existing flash messages:
     *  * getFlashes() returns all the flash messages
     *  * getFlashes('notice') returns a simple array with flash messages of that type
     *  * getFlashes(['notice', 'error']) returns a nested array of type => messages.
     *
     * @return array
     */
    public function getFlashes($types = null)
    {
        try {
            if (null === $session = $this->app->get('session')) {
                return [];
            }
        } catch (\RuntimeException $e) {
            return [];
        }

        if ($types === null || $types === '' || $types === []) {
            return $session->getFlashBag()->all();
        }

        if (\is_string($types)) {
            return $session->getFlashBag()->get($types);
        }

        $result = [];
        foreach ($types as $type) {
            $result[$type] = $session->getFlashBag()->get($type);
        }

        return $result;
    }

    /**
     * Get page title
     *
     * @see wp_get_document_title()
     *
     * @return string
     */
    public function getTitle()
    {
        $title = \get_bloginfo('name', 'display');

        switch (true) {
            case \is_404():
                $title = \__('Page not found');
                break;
            case \is_search():
                /* translators: %s: Search query. */
                $title = \sprintf(\__('Search Results for &#8220;%s&#8221;'), \get_search_query());
                break;
            case \is_front_page():
                $title = \get_bloginfo('name', 'display');
                break;
            case \is_post_type_archive():
                $title = \post_type_archive_title('', false);
                break;
            case \is_tax():
                $title = \single_term_title('', false);
                break;
            case \is_home() || \is_singular():
                $title = \single_post_title('', false);
                break;
            case \is_category() || \is_tag():
                $title = \single_term_title('', false);
                break;
            case \is_author() && \get_queried_object():
                $author = \get_queried_object();
                $title = $author->display_name;
                break;
            case \is_year():
                $title = \get_the_date(\_x('Y', 'yearly archives date format'));
                break;
            case \is_month():
                $title = \get_the_date(\_x('F Y', 'monthly archives date format'));
                break;
            case \is_day():
                $title = \get_the_date();
                break;
        }

        return $title;
    }
}
