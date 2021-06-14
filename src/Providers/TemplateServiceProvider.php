<?php

namespace Rareloop\Lumberjack\Providers;

use WP_Post;
use WP_Theme;

class TemplateServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Add templates
        \add_filter(
            'theme_templates',
            [$this, 'registerTemplates'],
            10,
            4
        );

        // Prevent cache for non cacheable templates
        \add_action('template_redirect', [$this, 'setTemplateCachePolicy']);
    }

    public function registerTemplates(array $post_templates, WP_Theme $wp_theme, ?WP_Post $post, string $post_type)
    {
        $templates = $this->app->get('config')->get('templates', []);

        $templates = \array_filter($templates, function (string $template) use ($post_type) {
            $allowed_post_types = $template::getPostTypes();
            return \in_array($post_type, $allowed_post_types, true);
        });

        if (empty($templates)) {
            return $post_templates;
        }

        foreach ($templates as $template) {
            $post_templates[
                \sprintf('%s.php', $template::getTemplate())
            ] = $template::getTemplateName();
        }

        return $post_templates;
    }

    public function setTemplateCachePolicy()
    {
        $templates = $this->app->get('config')->get('templates', []);
        foreach ($templates as $template) {
            if (!$template::isCurrentTemplate()) {
                return;
            }
            if (!$template::isCacheable() && !\defined('DONOTCACHEPAGE')) {
                \define('DONOTCACHEPAGE', true);
            }
        }
    }
}
