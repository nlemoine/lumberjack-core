<?php

namespace Rareloop\Lumberjack\Facets;

use WP_Query;

abstract class AbstractFacetTaxonomy extends AbstractFacet
{
    public function getType(): string
    {
        return AbstractFacet::TYPE_TAXONOMY;
    }

    public function setPostsClauses(array $clauses): array
    {
        $key = \esc_sql($this->key);
        $clauses['join'] .= " INNER JOIN {$this->wpdb->term_relationships} AS qf_tr ON qf_tr.object_id = {$this->wpdb->posts}.ID";
        $clauses['join'] .= " INNER JOIN {$this->wpdb->term_taxonomy} AS qf_tt ON qf_tt.term_taxonomy_id = qf_tr.term_taxonomy_id";
        $clauses['join'] .= " INNER JOIN {$this->wpdb->terms} AS qf_t ON qf_t.term_id = qf_tt.term_id";
        $clauses['where'] .= " AND qf_tt.taxonomy = \"{$key}\"";
        $clauses['fields'] = "qf_t.slug AS value, qf_t.name AS name, qf_t.term_id as term_id, qf_tt.parent as parent, COUNT(DISTINCT {$this->wpdb->posts}.ID) AS count";
        $clauses['groupby'] = 'qf_t.slug';
        $clauses['limits'] = '';
        $clauses['orderby'] = '';
        return $clauses;
    }

    public function filter(WP_Query $query)
    {
        if ($this->currentValue === null) {
            return;
        }

        $tax_query = $query->get('tax_query');
        if (!\is_array($tax_query)) {
            $tax_query = [
                'relation' => 'AND',
            ];
        }

        if (\is_array($this->currentValue)) {
            $tax_query[] = $this->currentValue;
        }
        $tax_query[] = [
            'taxonomy' => $this->key,
            'field'    => 'slug',
            'terms'    => $this->currentValue,
        ];

        $query->set('tax_query', $tax_query);
    }
}
