<?php

namespace Rareloop\Lumberjack\Providers;

class ContextServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \add_filter('timber/context', function ($context) {
            unset($context['http_host'], $context['wp_title'], $context['body_class'], $context['theme']);

            $context['debug'] = WP_DEBUG;
            $context['env'] = \defined('WP_ENV') ? WP_ENV : false;

            // Locale
            global $wp_locale;
            $context['locale'] = $wp_locale;

            // Options
            try {
                $fields = $this->app->get('fields.options');
            } catch (\Exception $e) {
                $fields = [];
            }

            $current_language = $this->app->get('locale.short');

            $context['current_lang'] = $current_language;

            // foreach ($fields as $field) {
            //     $acf_field_config = $field->getConfig();
            //     // i18n option, use non i18n name in template
            //     if ($current_language && isset($acf_field_config['i18n']) && $acf_field_config['i18n']) {
            //         $pattern = '/^([a-z|_|-]+)_' . $current_language . '$/';
            //         $original_name = \preg_replace($pattern, '$1', $field->getName());
            //         $context['options'][$original_name] = \get_field($field->getName(), 'option');
            //     } else {
            //         $context['options'][$field->getName()] = \get_field($field->getName(), 'option');
            //     }
            // }

            // WP Query
            global $wp_query;
            $context['query'] = $wp_query;

            return $context;
        }, 1);
    }
}
