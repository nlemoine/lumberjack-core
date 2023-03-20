<?php

namespace Rareloop\Lumberjack\Models;

use Rareloop\Lumberjack\Exceptions\PostTypeRegistrationException;
use Timber\Post;
use Timber\PostQuery;
use Timber\Timber;
use WP_Query;

abstract class AbstractPostType extends AbstractPost
{
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

        if (empty($config) && !\in_array($postType, \get_post_types([
            '_builtin' => true,
            'public'   => true,
        ]), true)) {
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

        // Rewrite rules cleanup
        \add_filter("{$postType}_rewrite_rules", function ($rules) use ($config) {
            return \array_filter($rules, function ($query, $regex) use ($config) {
                global $wp_rewrite;

                // Remove embed rules
                if (\strpos($query, 'embed=true') !== false) {
                    return false;
                }
                // Remove trackback rules
                if (\strpos($regex, 'trackback/') !== false) {
                    return false;
                }
                // Remove attachments rules
                if (\strpos($regex, '/attachment/([^/]+)/') !== false) {
                    return false;
                }
                // Remove feed rules
                if (empty($config['rewrite']['feeds']) && \strpos($regex, '(feed|rdf|rss|rss2|atom)') !== false) {
                    return false;
                }
                // Remove comments rules
                if (!\in_array('comments', $config['supports'] ?? [], true) && \strpos($regex, $wp_rewrite->comments_pagination_base) !== false) {
                    return false;
                }
                return true;
            }, ARRAY_FILTER_USE_BOTH);
        });

        \register_extended_post_type($postType, $config);

        \add_filter('timber/post/classmap', function ($post_class) use ($postType) {
            return \array_merge(
                (array) $post_class,
                [
                    $postType => static::class,
                ],
            );
        });
    }

    /**
     * Get all posts of this type
     *
     * @param  integer $perPage The number of items to return (defaults to all)
     */
    public static function all($perPage = -1, $orderby = 'menu_order', $order = 'ASC'): Iterable
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
     */
    public static function query(array $args = []): Iterable
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

    public function getTemplate(): ?string
    {
        return $this->_wp_page_template ? $this->_wp_page_template : null;
    }

    /**
     * Return the config to use to register the post type with WordPress
     * Second parameter of the `register_post_type` function:
     * https://codex.wordpress.org/Function_Reference/register_post_type
     *
     * @return array|null
     */
    public static function getPostTypeConfig(): array
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
     * @return array
     */
    private static function posts(array $args = [])
    {
        return Timber::get_posts($args);
    }
}
