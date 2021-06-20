<?php

namespace Rareloop\Lumberjack\Providers;

use Timber\Image;

class AcfServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Hide menu
        \add_filter('acf/settings/show_admin', function (bool $show): bool {
            return WP_DEBUG;
        });

        // Remove default filters
        \add_action('acf/init', function () {
            // Image
            $field_type = \acf_get_field_type('image');
            \remove_filter('acf/format_value/type=image', [$field_type, 'format_value']);

            // Gallery
            $field_type = \acf_get_field_type('gallery');
            \remove_filter('acf/format_value/type=gallery', [$field_type, 'format_value']);

            // Date
            $field_type = \acf_get_field_type('date_picker');
            \remove_filter('acf/format_value/type=date_picker', [$field_type, 'format_value']);
            \remove_filter('acf/update_value/type=date_picker', [$field_type, 'update_value']);

            // Datetime
            $field_type = \acf_get_field_type('date_time_picker');
            \remove_filter('acf/format_value/type=date_time_picker', [$field_type, 'format_value']);
            \remove_filter('acf/update_value/type=date_time_picker', [$field_type, 'update_value']);

            // oEmbed
            $field_type = \acf_get_field_type('oembed');
            \remove_filter('acf/format_value/type=oembed', [$field_type, 'format_value']);
        });

        // Add new filters
        \add_filter('acf/format_value/type=image', [$this, 'formatAcfImage'], 10, 3);
        \add_filter('acf/format_value/type=gallery', [$this, 'formatAcfGallery'], 10, 3);
        \add_filter('acf/format_value/type=date_picker', [$this, 'formatAcfDatePicker'], 10, 3);
        \add_filter('acf/update_value/type=date_picker', [$this, 'updateAcfDatePicker'], 10, 3);
        \add_filter('acf/format_value/type=date_time_picker', [$this, 'formatAcfDateTimePicker'], 10, 3);
        \add_filter('acf/update_value/type=date_time_picker', [$this, 'updateAcfDateTimePicker'], 10, 3);
        \add_filter('acf/format_value/type=oembed', [$this, 'formatAcfoEmbed'], 10, 3);
        \add_filter('acf/update_value/type=oembed', [$this, 'updateAcfoEmbed'], 10, 3);
    }

    /**
     * Undocumented function.
     *
     * @param string $value
     * @param int    $post_id
     * @param array  $field
     */
    public function formatAcfoEmbed($value, $post_id, $field)
    {
        if (!empty($value)) {
            $key = \md5($value);
            $value = $this->acf_oembed_get($value);
        }

        return $value;
    }

    /**
     * Undocumented function.
     *
     * @param string $value
     * @param int    $post_id
     * @param array  $field
     */
    public function updateAcfoEmbed($value, $post_id, $field)
    {
        if (!empty($value)) {
            $this->acf_oembed_get($value);
        }

        return $value;
    }

    /**
     * Undocumented function.
     *
     * @param string $value
     * @param int    $post_id
     * @param array  $field
     */
    public function formatAcfDatePicker($value, $post_id, $field)
    {
        return new \DateTime($value);
    }

    /**
     * Saves ACF Datepicker field to a standard MySQL format.
     *
     * This enforces standards and makes queries on dates easier with WP_Meta_Query
     *
     * @see https://developer.wordpress.org/reference/classes/wp_meta_query/#accepted-arguments
     *
     * @param string $value
     * @param int    $post_id
     * @param array  $field
     */
    public function updateAcfDatePicker($value, $post_id, $field)
    {
        return \acf_format_date($value, 'Y-m-d');
    }

    /**
     * Undocumented function.
     *
     * @param string $value
     * @param int    $post_id
     * @param array  $field
     */
    public function formatAcfDateTimePicker($value, $post_id, $field)
    {
        return new \DateTime($value);
    }

    /**
     * Saves ACF DateTimepicker field to a standard MySQL format.
     *
     * This enforces standards and makes queries on dates easier with WP_Meta_Query
     *
     * @see https://developer.wordpress.org/reference/classes/wp_meta_query/#accepted-arguments
     *
     * @param string $value
     * @param int    $post_id
     * @param array  $field
     */
    public function updateAcfDateTimePicker($value, $post_id, $field)
    {
        return \acf_format_date($value, 'Y-m-d H:i:s');
    }

    /**
     * Undocumented function.
     *
     * @param array $value
     * @param int   $post_id
     * @param array $field
     */
    public function formatAcfGallery($value, $post_id, $field)
    {
        if (empty($value)) {
            return false;
        }

        return \array_map(function ($attachment_id) {
            return new Image($attachment_id);
        }, $value);
    }

    /**
     * Undocumented function.
     *
     * @param string $value
     * @param int    $post_id
     * @param array  $field
     */
    public function formatAcfImage($value, $post_id, $field)
    {
        if (empty($value)) {
            return false;
        }

        if (!\is_numeric($value)) {
            return false;
        }

        $value = \intval($value);

        return new Image($value);
    }

    /**
     * Attempts to fetch the embed HTML for a provided URL using oEmbed.
     *
     * Checks for a cached result (stored as custom post or in the post meta).
     *
     * @see  \WP_Embed::shortcode()
     *
     * @param mixed $value   the URL to cache
     *
     * @return null|string the embed HTML on success, otherwise the original URL
     */
    private function acf_oembed_get($value)
    {
        if (empty($value)) {
            return $value;
        }

        global $wp_embed;
        $html = $wp_embed->shortcode([], $value);

        if ($html) {
            return $html;
        }

        return $value;
    }
}
