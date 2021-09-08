<?php

namespace Rareloop\Lumberjack\Providers;

use Rareloop\Lumberjack\Models\NavMenu;

class MenusServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \add_action('after_setup_theme', [$this, 'registerNavMenus']);
        \add_filter('timber/context', [$this, 'addMenusToContext']);
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

    /**
     * Add menus to context
     */
    public function addMenusToContext(array $context): array
    {
        $menus = $this->app->get('config')->get('menus.menus');
        $context['menus'] = !isset($context['menus']) ? [] : $context['menus'];

        foreach (\array_keys($menus) as $location) {
            if (!\has_nav_menu($location)) {
                continue;
            }
            // $cache_key = 'menu_' . $location . '_' . $current_language;

            if (isset($context['menus'][\str_replace('-', '_', $location)])) {
                continue;
            }

            $context['menus'][\str_replace('-', '_', $location)] = new NavMenu($location);
        }

        return $context;
    }
}
