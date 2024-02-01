<?php

namespace Rareloop\Lumberjack\Providers;

use Exception;
use Rareloop\Lumberjack\Config;
use WP_CLI;

class CommandServiceProvider extends ServiceProvider
{
    public function boot(Config $config)
    {
        if (!\class_exists('WP_CLI')) {
            return;
        }

        $commands = $config->get('commands', []);
        foreach ($commands as $command => $class) {
            try {
                $cmd = $this->app->get($class);
                WP_CLI::add_command($command, $cmd);
            } catch (Exception $e) {
            }
        }
    }
}
