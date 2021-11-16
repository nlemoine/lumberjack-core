<?php

namespace Rareloop\Lumberjack\Bootstrappers;

use Rareloop\Lumberjack\Application;

class RegisterLocale
{
    public function bootstrap(Application $app)
    {
        $app->singleton('locale', function () {
            return \get_locale();
        });

        $app->singleton('locale.short', function () use ($app) {
            return \substr($app->get('locale'), 0, 2);
        });

        if (\function_exists('locale_set_default')) {
            \locale_set_default(\str_replace('_', '-', $app->get('locale')));
        }
    }
}
