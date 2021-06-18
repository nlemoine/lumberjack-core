<?php

namespace Rareloop\Lumberjack\Providers;

use Rareloop\Lumberjack\Config;

class ThemeServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \add_action('after_setup_theme', [$this, 'addThemeSupport']);
        \add_action('after_setup_theme', [$this, 'addTranslations']);
    }

    public function addThemeSupport(): void
    {
        // Theme support
        $support = $this->getConfig('theme.support', []);
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
        $text_domain = $this->getConfig('theme.text_domain');
        if ($text_domain) {
            \load_theme_textdomain($text_domain, $this->app->get('paths.languages'));
        }
    }
}
