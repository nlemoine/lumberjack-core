<?php

namespace Rareloop\Lumberjack\Bootstrappers;

use Rareloop\Lumberjack\Application;

class RegisterProviders
{
    public function bootstrap(Application $app)
    {
        $config = $app->get('config');
        $providers = $config->get('app.providers', []);

        foreach ($providers as $provider) {
            $app->register($provider);
        }
    }
}
