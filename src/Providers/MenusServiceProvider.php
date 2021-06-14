<?php

namespace Rareloop\Lumberjack\Providers;

use Rareloop\Lumberjack\Config;

class MenusServiceProvider extends ServiceProvider
{
    public function boot(Config $config)
    {
        \add_action('after_setup_theme', function () use ($config) {
            $this->registerNavMenus($config);
        });
    }

    public function registerNavMenus(Config $config): void
    {
        $menus = $config->get('menus.menus', []);
        if (\count($menus)) {
            \register_nav_menus($menus);
        }
    }
}
