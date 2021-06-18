<?php

namespace Rareloop\Lumberjack\Providers;

class LocaleServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \locale_set_default(\str_replace('_', '-', $this->get('locale')));
    }

    public function register()
    {
        $this->app->bind('locale', function () {
            return \get_locale();
        });

        $this->app->bind('locale.short', function () {
            return \substr($this->get('locale'), 0, 2);
        });
    }
}
