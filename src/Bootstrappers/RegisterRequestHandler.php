<?php

namespace Rareloop\Lumberjack\Bootstrappers;

use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Config;

class RegisterRequestHandler
{
    public function bootstrap(Application $app)
    {
        $config = $app->get(Config::class);

        if ($config->get('app.debug')) {
            $app->detectWhenRequestHasNotBeenHandled();
        }
    }
}
