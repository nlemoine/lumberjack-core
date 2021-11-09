<?php

namespace Rareloop\Lumberjack\Facets;

use WP_Query;

/**
 * @see examples.php for some examples
 */
class Facets
{
    /**
     * the wp_query on wich we want to get the facets
     *
     * @var \WP_Query
     */
    protected $query;

    /**
     * all the facets we want to get from the $query
     *
     * @var array
     */
    protected $facets;

    /**
     * @var \wpdb
     */
    protected $wpdb;

    /**
     * used on filters to remember on wich current facet we need to work
     *
     * @var string
     */
    protected $currentFacet;

    public function __construct(array $args = [])
    {
        $this->wpdb = $GLOBALS['wpdb'];
        $this->facets = [];
        $default_args = [
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false,
            'no_found_rows'          => true,
            'ignore_sticky_posts'    => true,
        ];

        $args_list = [
            'post_type'      => $args['post_type'] ?? false,
            'posts_per_page' => $args['posts_per_page'] ?? false,
            'paged'          => $args['paged'] ?? false,
        ];

        $args = \array_merge($default_args, \array_filter($args_list));

        $this->query = new WP_Query();
        foreach ($args as $key => $value) {
            $this->query->set($key, $value);
        }
    }

    /**
     * Get facets
     */
    public function getFacets(): array
    {
        if (empty($this->facets)) {
            return [];
        }

        foreach ($this->facets as $facet) {
            $query = clone $this->query;
            $currentFacet = $facet;
            $facet->setQuery($query);

            foreach ($this->facets as $f) {
                if ($f->getKey() === $currentFacet->getKey()) {
                    continue;
                }
                $f->filter($query);
            }
        }

        return $this->facets;
    }

    /**
     * Add facet
     */
    public function addFacet(AbstractFacet $facet): self
    {
        $this->facets[$facet->getKey()] = $facet;

        return $this;
    }
}
