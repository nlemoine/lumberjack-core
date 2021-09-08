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
     * @var array
     */
    protected $calculatedFacets;

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
        ];

        $args_list = [
            'post_type'      => $args['post_type'] ?? false,
            'posts_per_page' => $args['posts_per_page'] ?? false,
            'paged'          => $args['paged'] ?? false,
        ];

        $args = \array_merge($default_args, \array_filter($args_list));

        $this->query = new WP_Query($args);
    }

    /**
     * Get facets
     */
    public function getFacets(): array
    {
        if (empty($this->facets)) {
            return [];
        }

        if ($this->calculatedFacets) {
            return $this->calculatedFacets;
        }
        $this->calculatedFacets = [];

        foreach ($this->facets as $facet) {
            $query = clone $this->query;
            $this->currentFacet = $facet;
            $facet->setQuery($query);

            // dump('current: ' . $facet->getName());

            // // apply filters but the current
            foreach ($this->facets as $f) {
                if ($f->getKey() === $this->currentFacet->getKey()) {
                    continue;
                }
                // dump('filter:'. $f->getName());
                $f->filter($query);
            }
            // dump($query->tax_query);
            // $facet->setQuery($query);
            // $this->calculatedFacets[$name] = $facet->getItems($query);
        }

        // return $this->calculatedFacets;
        return $this->facets;
    }

    /**
     * Add facet
     */
    public function addFacet(AbstractFacet $facet)
    {
        $this->facets[$facet->getKey()] = $facet;
    }
}
