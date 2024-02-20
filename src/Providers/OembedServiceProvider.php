<?php

namespace Rareloop\Lumberjack\Providers;

use Psr\Log\LoggerInterface;
use Rareloop\Lumberjack\Timber;
use Throwable;

class OembedServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $GLOBALS['wp_embed']->usecache = false;
        \add_filter('oembed_dataparse', [$this, 'saveOembedData'], 10, 3);
        \add_filter('embed_oembed_html', [$this, 'wrapEmbed'], 10, 4);
        \add_filter('oembed_providers', [$this, 'filterProviders']);
        \add_filter('oembed_ttl', [$this, 'setOembedTtl'], 10, 3);
        \add_filter('embed_oembed_discover', [$this, 'setOembedDiscovery']);
    }

    public function setOembedDiscovery(bool $discover): bool
    {
        return (bool) $this->getConfig('oembed.allow_discovery', $discover);
    }

    /**
     * Set oEmbed time to live
     *
     * @param string $url
     * @param array $attr
     * @param int $postId
     */
    public function setOembedTtl($url, $attr, $postId): int
    {
        // Set to 0 for debugging, will fetch the oembed data on every request
        return (int) $this->getConfig('oembed.ttl', MONTH_IN_SECONDS);
    }

    /**
     * Saves oEmbed data
     *
     * @param array $data
     */
    public function saveOembedData(string $html, $data, string $url): string
    {
        // Not in a shortcode embed context
        // if (!$this->wasCalledFromShortcode) {
        //     return $html;
        // }

        $cacheKey = $this->getCacheKey($url);

        $post = \get_post();
        $postID = $post->ID ?? null;
        if ($postID) {
            // There's a post context, save it to post meta
            \update_post_meta($postID, '_oembed_data_' . $cacheKey, (array) $data);
            return $html;
        }

        // Oembed cache exists, it has been saved as an "oembed_cache" post type
        // (e.g. embed rendering took place on a context where no post global were available)
        $oembedCachePostID = $GLOBALS['wp_embed']->find_oembed_post_id($cacheKey);
        if ($oembedCachePostID) {
            $this->updateOembedData((int) $oembedCachePostID, (array) $data);
            return $html;
        }

        // The oembed cache has not been saved yet (it happens later on the first request)
        // Use a one time filter to save data
        \add_filter('embed_oembed_html', function ($html, $url, $attr, $postID) use ($data, $cacheKey) {
            $oembedCachePostID = $GLOBALS['wp_embed']->find_oembed_post_id($cacheKey);
            if ($oembedCachePostID && $GLOBALS['wp_embed']->last_url === $url) {
                $this->updateOembedData((int) $oembedCachePostID, (array) $data);
            }
            \remove_filter('embed_oembed_html', __FUNCTION__, 1);
            return $html;
        }, 1, 4);

        return $html;
    }

    /**
     * Wrap embeds
     */
    public function wrapEmbed(string $html, string $url, array $attr, ?int $post_id): string
    {
        if (\is_admin() || (\defined('REST_REQUEST') && REST_REQUEST)) {
            return $html;
        }

        $data = $this->getEmbedData($url, $post_id);
        $provider_name = $data['provider_name'] ?? null;
        $type = $data['type'] ?? null;

        $templates = ['embed'];
        if ($type) {
            \array_unshift($templates, \mb_strtolower($type));
        }
        if ($provider_name) {
            \array_unshift($templates, \mb_strtolower($provider_name));
        }

        $templates = \apply_filters('app/oembed/templates', $templates, $data, $html, $url, $attr, $post_id);
        $templates = \array_map(fn ($t) => 'embeds/' . $t . '.html.twig', $templates);
        $data = \apply_filters('app/oembed/data', $data, $html, $url, $attr, $post_id);

        try {
            $embed_html = Timber::compile(
                $templates,
                $data
            );
            return empty($embed_html) ? $html : $embed_html;
        } catch (Throwable $th) {
            $this->get(LoggerInterface::class)->error($th);
            return $html;
        }
    }

    /**
     * Filter providers.
     */
    public function filterProviders(array $providers): array
    {
        $allowed_providers = $this->getConfig('oembed.allowed_providers', []);
        if (empty($allowed_providers)) {
            return $providers;
        }

        $allowed_providers_list = [];
        foreach ($providers as $k => $provider) {
            $provider_oembed_url = $provider[0] ?? null;
            if (!$provider_oembed_url) {
                continue;
            }
            foreach ($allowed_providers as $p) {
                if (\strpos($provider_oembed_url, $p) !== false) {
                    $allowed_providers_list[$k] = $provider;
                }
            }
        }

        return $allowed_providers_list;
    }

    /**
     * Save oEmbed data into post_excerpt
     */
    protected function updateOembedData(int $postID, array $data): void
    {
        global $wpdb;
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->update(
            $wpdb->posts,
            [
                'post_excerpt' => \maybe_serialize($data),
            ],
            [
                'ID' => $postID,
            ]
        );

        // Clear cache so we get fresh data when rendering the embed
        \clean_post_cache($postID);
    }

    /**
     * Get embed data
     *
     * @return array|null
     */
    protected function getEmbedData(string $url, int $post_id): array
    {
        $cacheKey = $this->getCacheKey($url);

        $data = \get_post_meta($post_id, \sprintf('_oembed_data_%s', $cacheKey), true);
        if (!empty($data)) {
            return (array) $data;
        }

        // No data was found in post ID, try oembed cache
        $oembedCachePostID = $GLOBALS['wp_embed']->find_oembed_post_id($cacheKey);
        if (!$oembedCachePostID) {
            return [];
        }

        $post = \get_post($oembedCachePostID);
        return isset($post->post_excerpt) ? (array) \maybe_unserialize($post->post_excerpt) : [];
    }

    /**
     * Get cache key for URL
     */
    protected function getCacheKey(string $url): string
    {
        // phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
        return \md5($url . \serialize($GLOBALS['wp_embed']->last_attr));
    }
}
