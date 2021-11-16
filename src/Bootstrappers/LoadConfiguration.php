<?php

namespace Rareloop\Lumberjack\Bootstrappers;

use function DI\get;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Config;

class LoadConfiguration
{
    public function bootstrap(Application $app)
    {
        $config = new Config($app->configPath());

        $app->bind(Config::class, $config);
        $app->bind('config', get(Config::class));
    }
}
