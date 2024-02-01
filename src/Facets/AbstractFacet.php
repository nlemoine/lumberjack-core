<?php

namespace Rareloop\Lumberjack\Facets;

use Exception;
use WP_Query;

abstract class AbstractFacet
{
    public const TYPE_TAXONOMY = 'taxonomy';

    public const TYPE_META = 'meta';

    public const TYPE_COLUMN = 'column';

    public const MODE_EXCLUDE = 'exclude';

    public const MODE_INCLUDE = 'include';

    public ?WP_Query $query = null;

    protected string $key;

    protected ?string $name = null;

    protected ?string $label = null;

    protected ?string $labelAll = null;

    protected string $type;

    protected ?array $items = null;

    protected string $mode = self::MODE_INCLUDE;

    protected $currentValue = null;

    protected $wpdb;

    public function __construct()
    {
        if (!\in_array($this->getType(), [self::TYPE_TAXONOMY, self::TYPE_META, self::TYPE_COLUMN], true)) {
            throw new Exception('Invalid facet type');
        }
        $this->type = $this->getType();
        $this->key = $this->getKey();
        $this->name = $this->getName() ?? $this->getKey();
        $this->label = $this->getLabel();
        $this->labelAll = $this->getLabelAll();
        $this->currentValue = $this->getCurrentValue();
        $this->mode = $this->getMode();
        $this->wpdb = $GLOBALS['wpdb'];
    }

    abstract public function getKey(): string;

    abstract public function getName(): ?string;

    abstract public function getType(): string;

    public function getMode(): string
    {
        return $this->mode;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getLabelAll(): ?string
    {
        return $this->labelAll;
    }

    public function getCurrentValue()
    {
        return \get_query_var($this->getName()) ?: null;
    }

    public function getItems(): array
    {
        if ($this->items === null) {
            \add_filter('posts_request', [$this, 'setPostsRequest']);
            \add_filter('posts_clauses', [$this, 'setPostsClauses']);
            $this->query->get_posts();
            \remove_filter('posts_clauses', [$this, 'setPostsClauses']);
            \remove_filter('posts_request', [$this, 'setPostsRequest']);
        }

        $int_types = [
            'term_id',
            'count',
            'parent',
        ];
        $this->items = \array_map(function ($item) use ($int_types) {
            foreach ($int_types as $int_type) {
                if (isset($item->{$int_type})) {
                    $item->{$int_type} = (int) $item->{$int_type};
                }
            }
            return $item;
        }, $this->items);

        return $this->itemsCallback($this->items);
    }

    /**
     * Prevent the query to run
     * Get all the possible values for a facet instead
     */
    public function setPostsRequest(string $request): string
    {
        $items = $this->wpdb->get_results($request);
        foreach ($items as $item) {
            if (\is_array($this->currentValue)) {
                $item->current = \in_array($item->value, $this->currentValue, true);
            } else {
                $item->current = $item->value === $this->currentValue;
            }
        }

        $this->items = $items;

        return '';
    }

    public function setQuery(WP_Query $query): void
    {
        $this->query = $query;
    }

    /**
     * Undocumented function
     */
    protected function itemsCallback(array $items): array
    {
        return $items;
    }
}
