<?php

namespace Rareloop\Lumberjack\Providers;

use Rareloop\Lumberjack\Models\AbstractPostTemplate;
use Rareloop\Lumberjack\Template\AbstractTemplate;
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
        $templates = $this->getConfig('templates', []);

        $templates = \array_filter($templates, static function (string $template_class): bool {
            return \is_subclass_of($template_class, AbstractPostTemplate::class) || \is_subclass_of($template_class, AbstractTemplate::class);
        });

        $templates = \array_filter($templates, function (string $template_class) use ($post_type) {
            $allowed_post_types = $template_class::getPostTypes();
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
        $templates = $this->getConfig('templates', []);
        foreach ($templates as $template_class) {
            if (!\is_subclass_of($template_class, AbstractTemplate::class) || !\is_subclass_of($template_class, AbstractPostTemplate::class)) {
                continue;
            }
            if (!\is_page_template($template_class::getTemplate() . '.php')) {
                return;
            }
            if (!$template_class::isCacheable() && !\defined('DONOTCACHEPAGE')) {
                \define('DONOTCACHEPAGE', true);
            }
        }
    }
}
