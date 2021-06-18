<?php

namespace Rareloop\Lumberjack\Providers;

class MenusServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \add_action('after_setup_theme', [$this, 'registerNavMenus']);
    }

    public function registerNavMenus(): void
    {
        $menus = $this->getConfig('menus.menus', []);
        if (\count($menus)) {
            \register_nav_menus($menus);
        }
    }
}
