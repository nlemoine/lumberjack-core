<?php

namespace Rareloop\Lumberjack\Facets;

use WP_Query;

abstract class AbstractFacetMeta extends AbstractFacet
{
    public function getType(): string
    {
        return AbstractFacet::TYPE_META;
    }

    public function setPostsClauses(array $clauses): array
    {
        $alias = 'qf_' . $this->type;
        $clauses['join'] .= ' INNER JOIN ' . $this->wpdb->postmeta . ' AS ' . $alias . ' ON ' . $alias . '.post_id = ' . $this->wpdb->posts . '.ID AND ' . $alias . '.meta_key = "' . \esc_sql($this->key) . '"';
        $clauses['fields'] = $alias . '.meta_value AS value, COUNT(DISTINCT ' . $this->wpdb->posts . '.ID) AS count';
        $clauses['groupby'] = $alias . '.meta_value';
        $clauses['limits'] = '';
        $clauses['orderby'] = '';
        return $clauses;
    }

    public function filter(WP_Query $query)
    {
        $value = $this->getValue();

        if ($value === null) {
            return;
        }

        $meta_query = $query->get('meta_query');
        if (!\is_array($meta_query)) {
            $meta_query = [
                'relation' => 'AND',
            ];
        }
        if (\is_array($value)) {
            $meta_query[] = $value;
        }
        $meta_query[] = [
            'key'     => $this->key,
            'compare' => '=',
            'value'   => $value,
        ];
        $query->set('meta_query', $meta_query);

        return;
    }
}
