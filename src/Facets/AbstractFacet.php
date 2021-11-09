<?php

namespace Rareloop\Lumberjack\Facets;

use Doctrine\SqlFormatter\NullHighlighter;
use Doctrine\SqlFormatter\SqlFormatter;
use WP_Query;

abstract class AbstractFacet
{
    public const TYPE_TAXONOMY = 'taxonomy';

    public const TYPE_META = 'meta';

    public const TYPE_COLUMN = 'column';

    public ?WP_Query $query = null;

    protected string $key;

    protected ?string $name = null;

    protected ?string $label = null;

    protected ?string $labelAll = null;

    protected string $type;

    protected ?array $items = null;

    protected $currentValue = null;

    protected $wpdb;

    public function __construct()
    {
        if (!\in_array($this->getType(), [self::TYPE_TAXONOMY, self::TYPE_META, self::TYPE_COLUMN], true)) {
            throw new \Exception('Invalid facet type');
        }
        $this->type = $this->getType();
        $this->key = $this->getKey();
        $this->name = $this->getName() ?? $this->getKey();
        $this->label = $this->getLabel();
        $this->labelAll = $this->getLabelAll();
        $this->currentValue = $this->getCurrentValue();
        $this->wpdb = $GLOBALS['wpdb'];
    }

    /**
     * Get key
     */
    abstract public function getKey(): string;

    /**
     * Get name
     */
    abstract public function getName(): ?string;

    /**
     * Get type
     */
    abstract public function getType(): string;

    /**
     * Get label
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * Get label all
     */
    public function getLabelAll(): ?string
    {
        return $this->labelAll;
    }

    /**
     * Get value
     */
    public function getCurrentValue()
    {
        return \get_query_var($this->getName()) ?: null;
    }

    /**
     * Get items
     */
    public function getItems(): array
    {
        if ($this->items === null) {
            \add_filter('posts_request', [$this, 'setPostsRequest']);
            \add_filter('posts_clauses', [$this, 'setPostsClauses']);
            $this->query->get_posts();
            \remove_filter('posts_clauses', [$this, 'setPostsClauses']);
            \remove_filter('posts_request', [$this, 'setPostsRequest']);
        }

        return $this->itemsCallback($this->items);
    }

    /**
     * Prevent the query to run
     * Get all the possible values for a facet instead
     */
    public function setPostsRequest(string $request): string
    {
        $items = $this->wpdb->get_results($request);
        // \dump((new SqlFormatter(new NullHighlighter()))->format($request));

        foreach ($items as $item) {
            $item->current = $item->value === $this->currentValue;
        }

        $this->items = $items;

        return '';
    }

    /**
     * Set query
     */
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
