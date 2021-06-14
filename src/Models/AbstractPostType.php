<?php

namespace Rareloop\Lumberjack\Models;

use Rareloop\Lumberjack\Exceptions\PostTypeRegistrationException;
use Spatie\Macroable\Macroable;
use Timber\Post;
use Timber\PostQuery;
use Timber\Timber;
use WP_Query;

abstract class AbstractPostType extends Post
{
    use Macroable {
        Macroable::__call as __macroableCall;
        Macroable::__callStatic as __macroableCallStatic;
    }

    public function __construct($id = null, $preventTimberInit = false)
    {
        /**
         * There are occasions where we do not want the bootstrap the data. At the moment this is
         * designed to make Query Scopes possible
         */
        if (!$preventTimberInit) {
            parent::__construct($id);
        }
    }

    public function __call($name, $arguments)
    {
        if (static::hasMacro($name)) {
            return $this->__macroableCall($name, $arguments);
        }

        return parent::__call($name, $arguments);
    }

    public static function __callStatic($name, $arguments)
    {
        if (static::hasMacro($name)) {
            return static::__macroableCallStatic($name, $arguments);
        }

        \trigger_error('Call to undefined method ' . __CLASS__ . '::' . $name . '()', E_USER_ERROR);
    }

    /**
     * Return the key used to register the post type with WordPress
     * First parameter of the `register_post_type` function:
     * https://codex.wordpress.org/Function_Reference/register_post_type
     */
    abstract public static function getPostType(): string;

    /**
     * Register this PostType with WordPress
     */
    public static function register(): void
    {
        $postType = static::getPostType();
        $config = static::getPostTypeConfig();

        if (empty($postType)) {
            throw new PostTypeRegistrationException('Post type not set');
        }

        if (empty($config) && !\in_array($postType, ['page', 'post'], true)) {
            throw new PostTypeRegistrationException('Config not set');
        }

        $defaultConfig = static::getDefaultConfig();
        $config = \array_merge($defaultConfig, $config);
        $config = \array_merge($config, [
            'labels' => static::getLabels(),
        ]);
        $config = \array_merge($config, [
            'admin_cols' => static::getAdminColumns(),
        ]);
        $config = \array_merge($config, [
            'admin_filters' => static::getAdminFilters(),
        ]);

        \register_extended_post_type($postType, $config);

        \add_filter('Timber\PostClassMap', function ($post_class) use ($postType) {
            return \array_merge(
                [
                    $postType => static::class,
                ],
                (array) $post_class
            );
        });

        // Set default query
        $args = static::getDefaultQuery();
        if (!empty($args)) {
            \add_filter('pre_get_posts', function (WP_Query $wp_query) use ($args, $postType) {
                if (\is_admin()) {
                    return;
                }
                if (!$wp_query->is_main_query()) {
                    return;
                }
                if ($wp_query->is_singular()) {
                    return;
                }
                if (!\in_array($postType, (array) $wp_query->get('post_type'), true)) {
                    return;
                }
                foreach ($args as $key => $value) {
                    $wp_query->set($key, $value);
                }
            });
        }
    }

    /**
     * Get all posts of this type
     *
     * @param  integer $perPage The number of items to return (defaults to all)
     * @return \Illuminate\Support\Collection
     */
    public static function all($perPage = -1, $orderby = 'menu_order', $order = 'ASC')
    {
        $order = \strtoupper($order);

        $args = [
            'posts_per_page' => $perPage,
            'orderby'        => $orderby,
            'order'          => $order,
        ];

        return static::query($args);
    }

    /**
     * Convenience function that takes a standard set of WP_Query arguments but mixes it with
     * arguments that mean we're selecting the right post type
     *
     * @param  array $args standard WP_Query array
     * @return \Illuminate\Support\Collection
     */
    public static function query(array $args = []): PostQuery
    {
        // Set the correct post type
        $args = \array_merge($args, [
            'post_type' => static::getPostType(),
        ]);

        if (!isset($args['post_status'])) {
            $args['post_status'] = 'publish';
        }

        return static::posts($args);
    }

    public static function getDefaultConfig(): array
    {
        return [
            'hierarchical' => false,
        ];
    }

    public static function getArchiveUrl(): string
    {
        return \get_post_type_archive_link(static::getPostType());
    }

    public static function getDefaultQuery(): array
    {
        return [];
    }

    public function embed($url)
    {
        global $wp_embed;

        return $wp_embed->shortcode([], $url);
    }

    public function getFirstPost(PostQuery $posts): Post
    {
        if (\count($posts) === 0) {
            return false;
        }

        return $posts[0];
    }

    /**
     * Return the config to use to register the post type with WordPress
     * Second parameter of the `register_post_type` function:
     * https://codex.wordpress.org/Function_Reference/register_post_type
     *
     * @return array|null
     */
    protected static function getPostTypeConfig(): array
    {
        return [];
    }

    protected static function getLabels(): array
    {
        return [];
    }

    protected static function getAdminColumns(): array
    {
        return [];
    }

    protected static function getAdminFilters(): array
    {
        return [];
    }

    /**
     * Raw query function that uses the arguments provided to make a call to Timber::get_posts
     * and casts the returning data in instances of ourself.
     *
     * @param  array $args standard WP_Query array
     * @return \Illuminate\Support\Collection
     */
    private static function posts(array $args = [])
    {
        return Timber::get_posts($args, static::class);
    }
}
