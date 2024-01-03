<?php

namespace Rareloop\Lumberjack\Bootstrappers;

use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Config;
use function DI\get;

class LoadConfiguration
{
    public function bootstrap(Application $app)
    {
        $config = new Config($app->configPath());

        $app->bind(Config::class, $config);
        $app->bind('config', get(Config::class));
    }
}
