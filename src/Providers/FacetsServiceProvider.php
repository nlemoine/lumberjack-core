<?php

namespace Rareloop\Lumberjack\Providers;

use Rareloop\Lumberjack\Facets\AbstractFacet;
use Rareloop\Lumberjack\Facets\AbstractFacetTaxonomy;
use WP_Query;

class FacetsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \add_action('parse_tax_query', [$this, 'handleFacetsQuery'], 100);
    }

    public function handleFacetsQuery(WP_Query $query): void
    {
        if (!$query->is_main_query()) {
            return;
        }

        if (!$query->is_archive && empty($query->is_page_for_custom_post_type) ) {
            return;
        }

        $facets = array_filter(array_map(function(string $facetClass): AbstractFacet {
            return new $facetClass();
        }, $this->getConfig('facets', [])), function($facet) {
            return $facet->getMode() === AbstractFacet::MODE_EXCLUDE;
        });

        if(empty($facets)) {
            return;
        }

        foreach($facets as $facet) {
            if ($facet->getType() === AbstractFacet::TYPE_TAXONOMY && $query->get($facet->getName())) {
                if(empty($query->tax_query)) {
                    continue;
                }
                $this->handleTaxonomyExclusion($query, $facet);
            }
        }
    }

    protected function handleTaxonomyExclusion(WP_Query &$query, AbstractFacetTaxonomy $facet): void {
        foreach($query->tax_query->queries as $key => $q) {
            if(isset($q['taxonomy']) && $q['taxonomy'] === $facet->getKey()) {
                $query->tax_query->queries[$key]['operator'] = 'NOT IN';
            }
        }
    }
}
