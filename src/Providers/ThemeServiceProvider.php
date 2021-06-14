<?php

namespace Rareloop\Lumberjack\Providers;

use Rareloop\Lumberjack\Config;

class ThemeServiceProvider extends ServiceProvider
{
    public function boot(Config $config)
    {
        \add_action('after_setup_theme', function () use ($config) {
            $this->addThemeSupport($config);
            $this->addTranslations($config);
        });
    }

    public function addThemeSupport(Config $config): void
    {
        // Theme support
        $support = $config->get('theme.support', []);
        foreach ($support as $key => $value) {
            if (\is_numeric($key)) {
                \add_theme_support($value);
            } else {
                \add_theme_support($key, $value);
            }
        }
    }

    public function addTranslations(Config $config): void
    {
        // Translations
        $text_domain = $config->get('theme.text_domain');
        if ($text_domain) {
            \load_theme_textdomain($text_domain, $this->app->get('paths.languages'));
        }
    }
}
