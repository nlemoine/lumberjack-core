<?php

namespace Rareloop\Lumberjack\Providers;

use Psr\Log\LoggerInterface;
use Timber\Timber;

class OembedServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \add_filter('oembed_dataparse', [$this, 'saveOembedData'], 10, 3);
        \add_filter('embed_oembed_html', [$this, 'wrapEmbed'], 10, 4);
        \add_filter('oembed_providers', [$this, 'filterProviders']);
    }

    /**
     * Saves oEmbed data
     *
     * @param array $data
     */
    public function saveOembedData(string $html, $data, string $url): string
    {
        $post = \get_post();
        if (empty($post->ID)) {
            return $html;
        }

        \update_post_meta($post->ID, '_oembed_data_' . \md5($url), (array) $data);

        return $html;
    }

    /**
     * Wrap embeds
     */
    public function wrapEmbed(string $html, string $url, array $attr, int $post_id): string
    {
        if (\is_admin() || (\defined('REST_REQUEST') && REST_REQUEST)) {
            return $html;
        }

        $embed_data = \get_post_meta($post_id, '_oembed_data_' . \md5($url), true);
        $embed_data['canonical_url'] = $url;

        if (!isset($embed_data['type'])) {
            return $html;
        }

        $templates = [
            \sprintf('embeds/%s.html.twig', \mb_strtolower($embed_data['provider_name'] ?? '')),
            \sprintf('embeds/%s.html.twig', $embed_data['type'] ?? 'embed'),
            'embeds/embed.html.twig',
        ];

        try {
            $embed_html = $this->get(Timber::class)::fetch(
                $templates,
                $embed_data
            );
            return empty($embed_html) ? $html : $embed_html;
        } catch (\Throwable $th) {
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
        if (empty($providers)) {
            return [];
        }

        return \array_filter($providers, function ($provider) use ($allowed_providers) {
            if (!isset($provider[0])) {
                return true;
            }

            return \in_array($provider[0], $allowed_providers, true);
        });
    }
}
