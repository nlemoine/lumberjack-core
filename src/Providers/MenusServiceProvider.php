<?php

namespace Rareloop\Lumberjack\Providers;

use Rareloop\Lumberjack\Models\NavMenu;
use Rareloop\Lumberjack\Models\NavMenuItem;
use Timber\Menu;
use Timber\MenuItem;
use WP_Post;
use WP_Term;

class MenusServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \add_action('after_setup_theme', [$this, 'registerNavMenus']);
        \add_filter('timber/menu/class', [$this, 'setDefaultNavMenuClass'], 10, 2);
        \add_filter('timber/menuitem/class', [$this, 'setDefaultNavMenuItemClass'], 10, 3);
    }

    public function setDefaultNavMenuClass(string $class, WP_Term $term): string
    {
        return $class === Menu::class ? NavMenu::class : $class;
    }

    public function setDefaultNavMenuItemClass(string $class, WP_Post $post, $menu): string
    {
        /**
         * Map in the shape of 'location' => 'class'
         */
        $menu_item_class_map = $this->getConfig('menus.menu_item_classes', []);
        foreach ($menu_item_class_map as $location => $item_class) {
            if ($menu->theme_location === $location) {
                return $item_class;
            }
        }

        return $class === MenuItem::class ? NavMenuItem::class : $class;
    }

    /**
     * Register nav menus
     */
    public function registerNavMenus(): void
    {
        $menus = $this->getConfig('menus.menus', []);
        if (\count($menus)) {
            \register_nav_menus($menus);
        }
    }
}
